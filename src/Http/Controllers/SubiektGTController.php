<?php

namespace Aliaswpeu\SferaApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Aliaswpeu\SferaApi\DTOs\DokumentDTO;
use Aliaswpeu\SferaApi\DTOs\KontrahentDTO;
use Aliaswpeu\SferaApi\Services\SubiektGTService;

class SubiektGTController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }



    public function store(Request $request)
    {
        $validated = $request->validate(
            KontrahentDTO::rules() + [
                'instance' => ['required', 'in:NNTB,PE'],
            ]
        );

        $instance = $validated['instance'];
        unset($validated['instance']);

        $dto = KontrahentDTO::fromArray($validated);

        $service = new SubiektGTService($instance);

        $data = $service->createKontrahent($dto);

        return response()->json($data);
    }

    public function storeOrder(Request $request)
    {
        // Validate request using DokumentDTO rules
        $validated = $request->validate(
            DokumentDTO::rules() + [
                'instance' => ['required', 'in:NNTB,PE'],
            ]
        );

        $instance = $validated['instance'];
        unset($validated['instance']);

        // Convert array → DTO
        $dto = DokumentDTO::fromArray($validated);

        // Create service
        $service = new SubiektGTService($instance);

        // Create document (ZK, FS, PAi, etc.)
        $data = $service->createDokument($dto);

        return response()->json($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
