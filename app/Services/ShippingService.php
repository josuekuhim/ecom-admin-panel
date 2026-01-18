<?php

namespace App\Services;

use App\Contracts\ShippingGateway;
use Illuminate\Support\Facades\Http;

class ShippingService implements ShippingGateway
{
    protected $baseUri;

    public function __construct()
    {
        // We'll use a public Correios API for demonstration
        $this->baseUri = 'https://ws.correios.com.br';
    }

    /**
     * Calculate shipping costs.
     *
     * @param string $destinationCep
     * @param float $weightInKg
     * @param float $lengthInCm
     * @param float $heightInCm
     * @param float $widthInCm
     * @return array
     */
    public function calculate(string $destinationCep, float $weightInKg, float $lengthInCm, float $heightInCm, float $widthInCm)
    {
        $originCep = preg_replace('/\D/', '', (string) config('shipping.origin_cep')) ?: '01001000';

        // Normalized fallback rates (dev-safe) used on failure
        $mock = [
            'pac' => [
                'price' => '25.50',
                'deadline' => '5 dias úteis',
            ],
            'sedex' => [
                'price' => '45.70',
                'deadline' => '2 dias úteis',
            ],
        ];

        try {
            // Simplified example: call Correios calculator and ignore response parsing for now
            $response = Http::timeout(5)->get($this->baseUri . '/calculador/CalcPrecoPrazo.aspx', [
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '04014', // 04014 = SEDEX, 04510 = PAC
                'sCepOrigem' => $originCep,
                'sCepDestino' => $destinationCep,
                'nVlPeso' => max(0.3, $weightInKg), // Correios expects >= 0.3kg
                'nCdFormato' => 1, // 1 = Box/Package
                'nVlComprimento' => max(16, $lengthInCm),
                'nVlAltura' => max(2, $heightInCm),
                'nVlLargura' => max(11, $widthInCm),
                'nVlDiametro' => 0,
                'sCdMaoPropria' => 'N',
                'nVlValorDeclarado' => 0,
                'sCdAvisoRecebimento' => 'N',
                'StrRetorno' => 'xml',
                'nIndicaCalculo' => 3,
            ]);

            if ($response->failed()) {
                \Log::warning('Correios shipping calc failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                // Use fallback in case of API failure
                return $mock;
            }

            // TODO: Parse XML for real values. For now return mock values.
            return $mock;
        } catch (\Throwable $e) {
            // Network or SSL errors should not break checkout in dev
            \Log::error('Correios shipping calc exception', [
                'message' => $e->getMessage(),
            ]);
            return $mock;
        }
    }
}
