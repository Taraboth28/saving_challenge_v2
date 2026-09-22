<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactions) {}

    public function index(TransactionFilterRequest $request): AnonymousResourceCollection
    {
        return TransactionResource::collection($this->transactions->list($request->filter()));
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        return TransactionResource::make($this->transactions->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $transaction): TransactionResource
    {
        return TransactionResource::make($this->transactions->findOrFail($transaction));
    }

    public function update(UpdateTransactionRequest $request, int $transaction): TransactionResource
    {
        return TransactionResource::make($this->transactions->update($transaction, $request->validated()));
    }

    public function destroy(int $transaction): Response
    {
        $this->transactions->delete($transaction);

        return response()->noContent();
    }
}
