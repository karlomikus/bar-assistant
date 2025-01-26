<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Response;
use Prometheus\RenderTextFormat;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\Log;

class MetricsController extends Controller
{
    public function index(CollectorRegistry $registry): Response
    {
        $renderer = new RenderTextFormat();

        try{
            $result = $renderer->render($registry->getMetricFamilySamples());
        } catch (Throwable $e) {
            Log::error('Unable to render metrics: ' . $e->getMessage());
            $result = '';
        }

        return new Response($result, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
