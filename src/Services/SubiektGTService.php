<?php

namespace Aliaswpeu\SferaApi\Services;

use COM;
use AsocialMedia\Sfera\GT;
use Illuminate\Support\Arr;
use AsocialMedia\Sfera\Program;
use Illuminate\Support\Facades\Log;
use Aliaswpeu\SferaApi\DTOs\TowarDTO;
use Aliaswpeu\SferaApi\DTOs\PozycjaDTO;
use Aliaswpeu\SferaApi\DTOs\DokumentDTO;
use Aliaswpeu\SferaApi\DTOs\KontrahentDTO;

class SubiektGTService
{
    protected Program $program;

    protected array $config;

    public function __construct(string $instance)
    {
        $this->config = config("sfera-api.$instance");

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
    public function createKontrahent(KontrahentDTO $dto): array
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
            if ($value !== null) {
                $Okh->$prop = $value;
            }
        }
    }
    private function mapDeliveryAddress($Okh, KontrahentDTO $dto): void
    {
        if (!$dto->AdresDostawy) {
            return;
        }
        $Okh->AdresDostawy = true;
        foreach ($dto->AdresDostawy as $key => $value) {
            if ($value !== null) {
                $Okh->{"AdrDost{$key}"} = $value;
            }
        }
    }
    private function mapCrmAddress($Okh, KontrahentDTO $dto): void
    {
        if (!$dto->AdresKorespondencyjny) {
            return;
        }
        $Okh->CrmAdresKorespondencyjny = true;
        foreach ($dto->AdresKorespondencyjny as $key => $value) {
            if ($value !== null) {
                $Okh->{"Crm{$key}"} = $value;
            }
        }
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

    public function createDokument(DokumentDTO $dto): array
    {
        Log::info('Sfera: Creating document', $dto->toArray());

        try {
            // 1. Create document by type
            $Dok = $this->createDocumentByType($dto->Typ);

            // 2. Assign customer
            $this->assignCustomer($Dok, $dto);
            // 3. Header fields
            $this->mapHeader($Dok, $dto);

            // 4. Add items
            foreach ($dto->Pozycje as $poz) {
                $this->addDocumentItem($Dok, $poz);
            }

            // 5. Payment
            $this->mapPayment($Dok, $dto);

            // 6. Save
            $Dok->Przelicz();
            $Dok->Zapisz();
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
        if ($dto->KontrahentId) {
            $Dok->KontrahentId = $dto->KontrahentId;
            return;
        }

        if ($dto->Kontrahent) {
            $kontrahentDto = KontrahentDTO::fromArray($dto->Kontrahent);
            $kontrahent = $this->createOrLoadKontrahent($kontrahentDto);
            $Dok->KontrahentId = $kontrahent->Identyfikator();
            return;
        }

        throw new \Exception("Document requires KontrahentId or Kontrahent");
    }
    private function createOrLoadKontrahent(KontrahentDTO $dto)
    {
        $mgr = $this->program->KontrahenciManager;

        if ($mgr->Istnieje($dto->Symbol)) {
            return $mgr->Wczytaj($dto->Symbol);
        }

        $Okh = $mgr->DodajKontrahenta();
        $this->mapBasicFields($Okh, $dto);
        $this->mapDeliveryAddress($Okh, $dto);
        $this->mapCrmAddress($Okh, $dto);
        $Okh->Zapisz();

        return $Okh;
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
    private function addDocumentItem($Dok, PozycjaDTO $dto)
    {
        // If full TowarDTO provided → create or load
        if ($dto->Towar instanceof TowarDTO) {
            $towar = $this->createOrLoadTowar($dto->Towar);
            $symbol = $towar->Symbol;
        } else {
            $symbol = $dto->Symbol;
        }

        $pos = $Dok->Pozycje->Dodaj($symbol);
        // dd($pos->TowarId);
        // Required
        $pos->IloscJm = $dto->Qty;
        $pos->WartoscBruttoPoRabacie = $dto->Qty * $dto->Price;

        // Optional
        if ($dto->PriceBeforeDiscount !== null) {
            $pos->WartoscBruttoPrzedRabatem = $dto->Qty * $dto->PriceBeforeDiscount;
        }

        if ($dto->Opis)
            $pos->Opis = $dto->Opis;
        if ($dto->Jm)
            $pos->Jm = $dto->Jm;
        if ($dto->VatId)
            $pos->VatId = $dto->VatId;
        if ($dto->RabatProcent !== null)
            $pos->RabatProcent = $dto->RabatProcent;
        if ($dto->MagazynId)
            $pos->MagazynId = $dto->MagazynId;
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
        $Dok->Tytul = $dto->Tytul ?? '';
        $Dok->Uwagi = $dto->Uwagi ?? '';
        $Dok->NumerOryginalny = $dto->NumerOryginalny ?? '';
        $Dok->Rezerwacja = $dto->Rezerwacja;

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
