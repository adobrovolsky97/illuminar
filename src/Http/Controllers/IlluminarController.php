<?php

namespace Adobrovolsky97\Illuminar\Http\Controllers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Formatters\HtmlDumper;
use Adobrovolsky97\Illuminar\Http\Requests\SearchRequest;
use Adobrovolsky97\Illuminar\Http\Resources\ItemResource;
use Adobrovolsky97\Illuminar\Support\EntryRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as Status;

/**
 * Class IlluminarController
 */
class IlluminarController extends Controller
{
    private const ASSET_TYPES = [
        'app.js'      => 'application/javascript; charset=utf-8',
        'app.css'     => 'text/css; charset=utf-8',
        'favicon.ico' => 'image/x-icon',
    ];

    /**
     * Show illuminar page
     */
    public function index(): View
    {
        return view('illuminar::main', [
            'assetVersion' => $this->assetVersion(),
            'dumpHeader'   => HtmlDumper::header(),
            'config'       => [
                'basePath' => rtrim(url(trim((string)config('illuminar.path', 'illuminar'), '/')), '/'),
                'theme'    => config('illuminar.theme', 'dark'),
                'appName'  => config('app.name'),
                'tracking' => array_keys(array_filter(array_map(function ($value) {
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }, (array)config('illuminar.track', [])))),
            ],
        ]);
    }

    /**
     * Get data to display
     */
    public function getData(SearchRequest $request, EntryRepository $entries): AnonymousResourceCollection
    {
        $filters = $request->validated();

        return ItemResource::collection($entries->search($filters))
            ->additional(['type_counts' => $entries->countByType($filters['context_id'] ?? null)]);
    }

    /**
     * Clear entries
     */
    public function clear(): JsonResponse
    {
        StorageDriverFactory::getDriverForConfig()->clear();

        return new JsonResponse(null, Status::HTTP_NO_CONTENT);
    }

    /**
     * Serve compiled UI assets directly from the package, no need to publish them
     */
    public function asset(string $file): BinaryFileResponse
    {
        $path = dirname(__DIR__, 3) . '/public/' . $file;

        abort_unless(isset(self::ASSET_TYPES[$file]) && is_file($path), 404);

        return response()->file($path, [
            'Content-Type'  => self::ASSET_TYPES[$file],
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function assetVersion(): string
    {
        $base = dirname(__DIR__, 3) . '/public/';

        return substr(md5((string)@filemtime($base . 'app.js') . (string)@filemtime($base . 'app.css')), 0, 12);
    }
}
