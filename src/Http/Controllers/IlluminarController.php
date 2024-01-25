<?php

namespace Adobrovolsky97\Illuminar\Http\Controllers;

use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Adobrovolsky97\Illuminar\Http\Requests\SearchRequest;
use Adobrovolsky97\Illuminar\Http\Resources\ItemResource;
use Adobrovolsky97\Illuminar\Illuminar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class IlluminarController
 */
class IlluminarController extends Controller
{
    /**
     * @var Illuminar
     */
    private Illuminar $illuminar;

    /**
     * @var SearchHelper
     */
    private SearchHelper $searchHelper;

    /**
     * @param Illuminar $illuminar
     * @param SearchHelper $searchHelper
     */
    public function __construct(Illuminar $illuminar, SearchHelper $searchHelper)
    {
        $this->illuminar = $illuminar;
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
        return ItemResource::collection($this->searchHelper->filterData($this->illuminar->getData(), $request->validated()));
    }
}
