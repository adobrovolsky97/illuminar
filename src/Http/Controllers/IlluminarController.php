<?php

namespace Adobrovolsky97\Illuminar\Http\Controllers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\Http\Requests\SearchRequest;
use Adobrovolsky97\Illuminar\Http\Resources\ItemResource;
use Adobrovolsky97\Illuminar\StorageDrivers\StorageDriverInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as Status;

/**
 * Class IlluminarController
 */
class IlluminarController extends Controller
{
    /**
     * @var StorageDriverInterface
     */
    private StorageDriverInterface $storageDriver;

    /**
     * @var SearchHelper
     */
    private SearchHelper $searchHelper;

    /**
     * @param SearchHelper $searchHelper
     */
    public function __construct(SearchHelper $searchHelper)
    {
        $this->storageDriver = StorageDriverFactory::getDriverForConfig();
        $this->searchHelper = $searchHelper;
    }

    /**
     * Show illuminar page
     *
     * @return View
     */
    public function index(): View
    {
        if (!config('illuminar.enabled')) {
            abort(404);
        }

        return view('illuminar::main');
    }

    /**
     * Get data to display
     *
     * @param SearchRequest $request
     * @return AnonymousResourceCollection
     */
    public function getData(SearchRequest $request): AnonymousResourceCollection
    {
        return ItemResource::collection(
            $this->searchHelper->filterData($this->storageDriver->getData(), $request->validated())
        );
    }

    /**
     * Clear entries
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        $this->storageDriver->clear();

        return Response::json(null, Status::HTTP_NO_CONTENT);
    }
}
