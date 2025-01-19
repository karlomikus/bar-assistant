<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Response;
use Prometheus\RenderTextFormat;
use Prometheus\CollectorRegistry;

class MetricsController extends Controller
{
    public function index(CollectorRegistry $registry): Response
    {
        $renderer = new RenderTextFormat();

        $result = $renderer->render($registry->getMetricFamilySamples());

        return new Response($result, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
