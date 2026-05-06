<?php

namespace Aliaswpeu\SferaApi\Services;

use Aliaswpeu\SferaApi\DTOs\DokumentDTO;
use Aliaswpeu\SferaApi\DTOs\KontrahentDTO;
use Aliaswpeu\SferaApi\DTOs\PozycjaDTO;
use Aliaswpeu\SferaApi\DTOs\TowarDTO;
use AsocialMedia\Sfera\GT;
use AsocialMedia\Sfera\Program;
use COM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubiektGTService
{
    protected Program $program;

    protected array $config;
    protected array $databaseConnection;
    protected string $instatnce;

    public function __construct(string $instance)
    {
        $this->instatnce = $instance;
        $this->config = config("sfera-api.$instance");

        config([
            "database.connections.$instance" => [
                'driver' => 'sqlsrv',
                'host' => $this->config['sfera_server'],
                'database' => $this->config['sfera_database'],
                'username' => $this->config['sfera_db_user'],
                'password' => $this->config['sfera_db_password'],
            ]
        ]);


        if (!$this->config) {
            throw new \Exception("Invalid Sfera instance: $instance");
        }

        $this->initializeGT();
    }



    /**
     * Initialize COM object for Subiekt GT
     */
    private function initializeGT(): void
    {
        try {

            $gt = new GT(
                $this->config['sfera_server'],
                $this->config['sfera_database'],
                $this->config['sfera_db_user'],
                $this->config['sfera_db_password'],
                $this->config['sfera_operator'],
                $this->config['sfera_operator_password']
            );

            $this->program = new Program(
                $gt,
                Program::SUBIEKT_GT,
                Program::ADJUST_USERNAME,
                Program::RUN_IN_BACKGROUND
            );

        } catch (\Throwable $e) {
            Log::error('Failed to initialize Subiekt GT COM object: ' . $e->getMessage());
            throw new \Exception('Subiekt GT initialization failed');
        }
    }


    # --------------------------------------------------------- 
    # KONTRAHENT 
    # --------------------------------------------------------- 
    public function createKontrahent2(KontrahentDTO $dto): array
    {
        Log::info('Sfera: Creating Kontrahent DTO', $dto->toArray());
        try {
            $Okh = $this->program->KontrahenciManager->DodajKontrahenta();
            $this->mapBasicFields($Okh, $dto);
            $this->mapDeliveryAddress($Okh, $dto);
            $this->mapCrmAddress($Okh, $dto);
            $Okh->Zapisz();
            return ['kh_Id' => $Okh->Identyfikator()];
        } catch (\com_exception $e) {
            Log::error(
                'Sfera COM exception when creating Kontrahent',
                [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'dto' => $dto->toArray(),
                ]
            );
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error(
                'Sfera PHP exception when creating Kontrahent',
                [
                    'message' => $e->getMessage(),
                    'dto' => $dto->toArray(),
                ]
            );
            return ['error' => $e->getMessage()];
        }
    }
    # --------------------------------------------------------- 
    # MAPPING HELPERS 
    # --------------------------------------------------------- 
    private function mapBasicFields($Okh, KontrahentDTO $dto): void
    {
        foreach ($dto->toArray() as $prop => $value) {
            // Skip grouped fields 
            if (in_array($prop, ['AdresDostawy', 'AdresKorespondencyjny'])) {
                continue;
            }
            if ($prop === 'Panstwo') {
                $value = $this->countryCodeToId($value);
            }
            if (
                $value !== null
                /*  && $prop !== 'NIP'
                 && $prop !== 'Miejscowosc'
                 && $prop !== 'KodPocztowy'
                 && $prop !== 'Ulica'
                 && $prop !== 'Nazwa'
                 && $prop !== 'Email'
                 && $prop !== 'Symbol' */
            ) {

                Log::info("Sfera: Mapping basic field for Kontrahent", ['property' => $prop, 'value' => $value]);
                $Okh->$prop = $value;
            }
        }

        /*     $Okh->Symbol = 'Test' . rand(1, 10000);
            $Okh->Nazwa = 'Test' . rand(1, 10000);
            $Okh->Nip = '1234567890';
            $Okh->Email = 'test@example.com';
            $Okh->Ulica = 'ulica testowa';
            $Okh->KodPocztowy = '42-200';
            $Okh->Miejscowosc = 'Częstochowa'; */
    }
    private function mapDeliveryAddress($Okh, KontrahentDTO $dto): void
    {
        if (!$dto->AdresDostawy) {
            return;
        }
        $Okh->AdresDostawy = true;
        foreach ($dto->AdresDostawy as $key => $value) {
            if ($key === 'Panstwo') {
                $value = $this->countryCodeToId($value);
            }
            if ($value !== null) {
                $Okh->{"AdrDost{$key}"} = $value;
                Log::info("Sfera: Mapping delivery address field for Kontrahent", ['property' => "AdrDost{$key}", 'value' => $value]);
            }
        }
        /*   $Okh->AdrDostKodPocztowy = '42-200';
          $Okh->AdrDostNazwa = 'Testowa 1';
          $Okh->AdrDostUlica = 'Testowa';
          $Okh->AdrDostMiejscowosc = 'Częstochowa'; */
    }
    private function mapCrmAddress($Okh, KontrahentDTO $dto): void
    {
        if (!$dto->AdresKorespondencyjny) {
            return;
        }
        $Okh->CrmAdresKorespondencyjny = true;
        foreach ($dto->AdresKorespondencyjny as $key => $value) {
            if ($key === 'Panstwo') {
                $value = $this->countryCodeToId($value);
            }
            if ($value !== null) {
                $Okh->{"Crm{$key}"} = $value;
            }
        }
    }



    public function createDokument(DokumentDTO $dto): array
    {
        Log::info('Sfera: Creating document', $dto->toArray());

        try {
            // 1. Create document by type
            $Dok = $this->createDocumentByType($dto->Typ);
            $Dok->AutoPrzeliczanie = false;
            // 2. Assign customer
            $this->assignCustomer($Dok, $dto);
            Log::info('Sfera: Customer assigned to document', ['KontrahentId' => $Dok->KontrahentId]);
            // 3. Header fields
            $this->mapHeader($Dok, $dto);
            Log::info('Sfera: Mapped document header fields', ['Typ' => $dto->Typ]);
            // 4. Add items
            foreach ($dto->Pozycje as $poz) {
                $this->addDocumentItem($Dok, $poz);
                Log::info('Sfera: Added document item', ['Pozycja' => $poz->toArray()]);
            }
            // 5. Payment
            $this->mapPayment($Dok, $dto);
            Log::info('Sfera: Mapped document payment', ['PaymentType' => $dto->PaymentType, 'Amount' => $dto->Amount]);
            // 6. Save
            $Dok->Przelicz();
            $Dok->Zapisz();
            Log::info('Sfera: Document saved successfully', ['doc_id' => $Dok->Identyfikator()]);
            return [
                'doc_ref' => $Dok->NumerPelny,
                'doc_id' => $Dok->Identyfikator(),
                'amount' => (float) $Dok->WartoscBrutto,
            ];

        } catch (\Throwable $e) {
            Log::error('Sfera: Failed to create document', [
                'message' => $e->getMessage(),
                'dto' => $dto->toArray(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }
    private function createDocumentByType(string $typ)
    {
        return match ($typ) {
            'ZK' => $this->program->SuDokumentyManager->DodajZK(),
            'FS' => $this->program->SuDokumentyManager->DodajFS(),
            'PAi' => $this->program->SuDokumentyManager->DodajPAi(),
            default => throw new \Exception("Unsupported document type: $typ"),
        };
    }
    private function assignCustomer($Dok, DokumentDTO $dto)
    {
        Log::info('Sfera: Trying to assign customer to document');
        if ($dto->KontrahentId) {
            $Dok->KontrahentId = $dto->KontrahentId;
            Log::info('Sfera: Assigned existing Kontrahent by ID', ['KontrahentId' => $dto->KontrahentId]);
            return;
        }

        if ($dto->Kontrahent) {
            // $kontrahentDto = KontrahentDTO::fromArray($dto->Kontrahent);
            $kontrahentDto = $dto->Kontrahent;
            $kontrahent = $this->createOrLoadKontrahent($kontrahentDto);
            $Dok->KontrahentId = $kontrahent->Identyfikator();
            Log::info('Sfera: Assigned Kontrahent by DTO', ['KontrahentId' => $kontrahent->Identyfikator()]);
            return;
        }

        throw new \Exception("Document requires KontrahentId or Kontrahent");
    }
    private function createOrLoadKontrahent(KontrahentDTO $dto)
    {
        $mgr = $this->program->KontrahenciManager;


        $kontrahenci = $this->findKontrahentByAny(
            nip: $dto->NIP,
            symbol: $dto->Symbol,
            email: $dto->Email,
        );
        if ($kontrahenci) {
            Log::info('Sfera: Found existing Kontrahent by NIP/Symbol/Email, loading first match', [
                'Symbol' => $dto->Symbol,
                'NIP' => $dto->NIP,
                'Email' => $dto->Email,
                'MatchesFound' => count($kontrahenci),
            ]);
            Log::debug('Sfera: Found existing Kontrahent', [
                'Kontrahenci' => $kontrahenci
            ]);
            // dd($kontrahenci[0]->kh_Id);
            return $mgr->Wczytaj((int) $kontrahenci[0]->kh_Id);
        }

        $Okh = $mgr->DodajKontrahenta();
        $this->mapBasicFields($Okh, $dto);
        Log::info('Sfera: Created new Kontrahent, mapping addresses', ['Symbol' => $dto->Symbol]);
        $this->mapDeliveryAddress($Okh, $dto);
        Log::info('Sfera: Mapped delivery address for Kontrahent', ['Symbol' => $dto->Symbol]);
        $this->mapCrmAddress($Okh, $dto);
        Log::info('Sfera: Mapped CRM address for Kontrahent', ['Symbol' => $dto->Symbol]);
        $Okh->Zapisz();

        return $Okh;
    }

    public function countryCodeToId(string $code)
    {
        return DB::connection($this->instatnce)
            ->table('sl_Panstwo')
            ->where('pa_KodPanstwaUE', $code)
            ->orWhere('pa_KodPanstwaISO', $code)
            ->value('pa_Id');
    }



    public function findKontrahentByAny(?string $nip = null, ?string $symbol = null, ?string $email = null)
    {
        $conditions = [];
        $params = [];

        if ($nip) {
            $conditions[] = "a.adr_NIP LIKE '%' + :nip + '%'";
            $params['nip'] = $nip;
        }

        if ($symbol) {
            $conditions[] = "k.kh_Symbol LIKE '%' + :symbol + '%'";
            $params['symbol'] = $symbol;
        }

        if ($email) {
            $conditions[] = "k.kh_EMail LIKE '%' + :email + '%'";
            $params['email'] = $email;
        }

        if (empty($conditions)) {
            return [];
        }

        $where = implode(' OR ', $conditions);

        $sql = "
    WITH latest_docs AS (
        SELECT 
            dok_PlatnikId,
            MAX(dok_DataWyst) AS last_doc_date
        FROM dok__Dokument
        GROUP BY dok_PlatnikId
    )
    SELECT 
        k.kh_Id,
        ld.last_doc_date
    FROM kh__Kontrahent k
    LEFT JOIN adr__Ewid a
        ON a.adr_IdObiektu = k.kh_Id
        AND a.adr_TypAdresu IN (1, 2, 11)
    LEFT JOIN latest_docs ld
        ON ld.dok_PlatnikId = k.kh_Id
    WHERE $where
    GROUP BY k.kh_Id, ld.last_doc_date
    ORDER BY 
        CASE WHEN ld.last_doc_date IS NULL THEN 1 ELSE 0 END,
        ld.last_doc_date DESC";
        return DB::connection($this->instatnce)->select($sql, $params);
    }


    /**
     * Creates a new product (towar) in Subiekt GT.
     */

    public function createTowar(TowarDTO $dto): array
    {
        Log::info('Sfera: Creating Towar DTO', $dto->toArray());

        try {
            // Correct API for AsocialMedia\Sfera
            $Otw = $this->program->TowaryManager->DodajTowar();

            // Set simple properties
            foreach ($dto->toArray() as $prop => $value) {
                if ($value !== null && !in_array($prop, ['PrimaryEan', 'AdditionalEans'])) {
                    $Otw->$prop = $value;
                }
            }

            // Primary EAN
            if ($dto->PrimaryEan) {
                $Otw->KodyKreskowe->Podstawowy = $dto->PrimaryEan;
            }

            // Additional EANs
            foreach ($dto->AdditionalEans as $ean) {
                $Otw->KodyKreskowe->Dodaj($ean);
            }

            $Otw->Zapisz();

            return ['tw_Id' => $Otw->Identyfikator()];

        } catch (\com_exception $e) {
            Log::error('Sfera COM exception when creating Towar', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'dto' => $dto->toArray(),
            ]);

            return ['error' => $e->getMessage()];

        } catch (\Throwable $e) {
            Log::error('Sfera PHP exception when creating Towar', [
                'message' => $e->getMessage(),
                'dto' => $dto->toArray(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }
    private function createOrLoadTowar(TowarDTO $dto)
    {
        $mgr = $this->program->TowaryManager;

        if ($mgr->Istnieje($dto->Symbol)) {
            return $mgr->Wczytaj($dto->Symbol);
        }

        $Otw = $mgr->DodajTowar();

        foreach ($dto->toArray() as $prop => $value) {
            if ($value !== null && !in_array($prop, ['PrimaryEan', 'AdditionalEans'])) {
                $Otw->$prop = $value;
            }
        }

        if ($dto->PrimaryEan) {
            $Otw->KodyKreskowe->Podstawowy = $dto->PrimaryEan;
        }

        foreach ($dto->AdditionalEans as $ean) {
            $Otw->KodyKreskowe->Dodaj($ean);
        }

        $Otw->Zapisz();

        return $Otw;
    }


    private function findTowar(?int $twId, ?string $symbol, ?string $ean)
    {
        // 1. Search by TwId
        if ($twId) {
            return DB::connection($this->instatnce)
                ->table('Tw__Towar')
                ->where('tw_Id', $twId)
                ->first();
        }

        // Build search term
        $search = trim($symbol ?? $ean ?? '');
        if ($search === '') {
            return null;
        }
        return DB::connection($this->instatnce)
            ->table('tw__Towar')
            ->where('tw_Symbol', $search)                // exact symbol
            ->orWhere('tw_PodstKodKresk', $search)       // exact EAN
            ->orWhereIn('tw_Id', function ($q) use ($search) {
                $q->select('kk_IdTowar')
                    ->from('tw_KodKreskowy')
                    ->where('kk_Kod', $search);
            })
            ->first();
    }
    private function addDocumentItem($Dok, PozycjaDTO $dto)
    {
        // 1. CASE: Full TowarDTO provided → create or load
        if ($dto->Towar instanceof TowarDTO) {
            $towar = $this->createOrLoadTowar($dto->Towar);
            return $this->addTowarPosition($Dok, $dto, $towar->Symbol);
        }

        // 2. CASE: Search by TwId, Symbol or Ean
        $towar = $this->findTowar(
            twId: $dto->TwId,
            symbol: $dto->Symbol,
            ean: $dto->Ean
        );
        if ($towar) {
            return $this->addTowarPosition($Dok, $dto, $towar->tw_Symbol);
        }
        // 3. CASE: No match → create one-time service
        return $this->addServicePosition($Dok, $dto);
    }
    private function addServicePosition($Dok, PozycjaDTO $dto)
    {
        try {
            $pos = $Dok->Pozycje->DodajUslugeJednorazowa();

            $pos->UslJednNazwa = Str::limit($dto->Opis, 49, '')
                ?? $dto->Symbol
                ?? $dto->Ean
                ?? 'Usługa jednorazowa';
            $pos->Opis = $dto->Opis ?? '';
            $pos->IloscJm = $dto->Qty;
            $pos->Jm = $dto->Jm ?? 'szt.';
            // $pos->CenaNettoPrzedRabatem = $dto->Price;
            $pos->CenaBruttoPrzedRabatem = $dto->Price;
            return $pos;
        } catch (\Throwable $e) {
            Log::error('Sfera: Failed to add one-time service position', [
                'message' => $e->getMessage(),
                'dto' => $dto->toArray(),
            ]);
            throw new \Exception('Failed to add one-time service position: ' . $e->getMessage());
        }
    }

    private function addTowarPosition($Dok, PozycjaDTO $dto, string $symbol)
    {
        $pos = $Dok->Pozycje->Dodaj($symbol);

        $pos->IloscJm = $dto->Qty;
        // $pos->WartoscBruttoPoRabacie = $dto->Qty * $dto->Price;
        $pos->CenaBruttoPrzedRabatem = $dto->Qty * $dto->Price;

        /*  if ($dto->PriceBeforeDiscount !== null) {
             $pos->CenaBruttoPrzedRabatem = $dto->Qty * $dto->PriceBeforeDiscount;
         } */

        if ($dto->Opis)
            $pos->Opis = $dto->Opis;
        if ($dto->Jm)
            $pos->Jm = $dto->Jm;
        if ($dto->VatId !== null)
            $pos->VatId = intval($dto->VatId);
        if ($dto->RabatProcent !== null)
            $pos->RabatProcent = $dto->RabatProcent;
        if ($dto->MagazynId !== null)
            $pos->MagazynId = intval($dto->MagazynId);
        if ($dto->OznaczenieJpkVat)
            $pos->OznaczenieJpkVat = $dto->OznaczenieJpkVat;
        if ($dto->PodlegaAkcyzie !== null)
            $pos->PodlegaAkcyzie = $dto->PodlegaAkcyzie;
        if ($dto->Termin)
            $pos->Termin = $dto->Termin;
        if ($dto->SymbolUDostawcy)
            $pos->TowarSymbolUDostawcy = $dto->SymbolUDostawcy;

        return $pos;
    }


    private function mapHeader($Dok, DokumentDTO $dto)
    {
        // $Dok->Tytul = $dto->Tytul ?? '';
        $Dok->Uwagi = $dto->Uwagi ?? '';
        // $Dok->NumerOryginalny = $dto->NumerOryginalny ?? '';
        // $Dok->Rezerwacja = $dto->Rezerwacja;

        if ($dto->DataWystawienia)
            $Dok->DataWystawienia = $dto->DataWystawienia;
        if ($dto->DataSprzedazy)
            $Dok->DataSprzedazy = $dto->DataSprzedazy;
        if ($dto->TerminRealizacji)
            $Dok->TerminRealizacji = $dto->TerminRealizacji;
        if ($dto->DataMagazynowa)
            $Dok->DataMagazynowa = $dto->DataMagazynowa;

        if ($dto->FlagaNazwa)
            $Dok->FlagaNazwa = $dto->FlagaNazwa;
        if ($dto->FlagaKomentarz)
            $Dok->FlagaKomentarz = $dto->FlagaKomentarz;

        if ($dto->MagazynNadawczyId)
            $Dok->MagazynNadawczyId = $dto->MagazynNadawczyId;
        if ($dto->KategoriaId)
            $Dok->KategoriaId = $dto->KategoriaId;
        if ($dto->PoziomCenyId)
            $Dok->PoziomCenyId = $dto->PoziomCenyId;
    }
    private function mapPayment($Dok, DokumentDTO $dto)
    {
        $amount = floatval($dto->Amount);

        switch ($dto->PaymentType) {
            case 'transfer':
                $Dok->PlatnoscPrzelewKwota = $amount;
                break;

            case 'card':
                $Dok->PlatnoscKartaKwota = $amount;
                $Dok->PlatnoscKartaId = intval($dto->PayPointId);
                break;

            /* PlatnoscGotowkaKwota Kwota gotówki wpłacana przez klienta. 
            PlatnoscGotowkaReszta Kwota reszty do wypłacenia klientowi. 
            PlatnoscKartaId Identyfikator płatności za pomocą karty płatniczej przy sprzedaży. 
            PlatnoscKartaKwota Kwota płatna kartą płatniczą przy sprzedaży. 
            PlatnoscKredytId Identyfikator płatności odroczonej (kredytu kupieckiego). 
            PlatnoscKredytKwota Kwota płatności odroczonej (kwota kredytu kupieckiego). 
            PlatnoscKredytTermin Termin płatności odroczonej (termin płatności kredytu kupieckiego). 
            PlatnoscPrzelewKwota Oznacza wartość płatną formą płatności "Zapłacono przelewem".  */



            case 'cash':
                $Dok->PlatnoscGotowkaKwota = $amount;
                break;

            case 'credit':
                $Dok->PlatnoscKredytKwota = $amount;
                break;

            case 'loan':
                $Dok->PlatnoscRatyKwota = $amount;
                break;

            default:
                $Dok->PlatnoscPrzelewKwota = $amount;
        }

        $Dok->LiczonyOdCenBrutto = true;
    }

}
