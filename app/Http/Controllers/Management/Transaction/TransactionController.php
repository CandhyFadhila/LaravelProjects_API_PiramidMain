<?php

namespace App\Http\Controllers\Management\Transaction;

use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Resources\Service\TransactionResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Gate::allows('masterdata.view')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            $query = Transaction::query()
                ->withTrashed()
                ->with([
                    'users',
                    'order_details',
                    'payment_details',
                    'transaction_statuses'
                ]);

            // filter
            $filterRules = [
                'order_detail_id' => fn($q, $val) => $q->whereIn('order_detail_id', (array) $val),
                'payment_detail_id'    => fn($q, $val) => $q->whereIn('payment_detail_id', (array) $val),
                'transaction_status_id'    => fn($q, $val) => $q->whereIn('transaction_status_id', (array) $val),
            ];

            $filters = $request->except(['limit', 'search']);
            $query   = QueryFilterSearch::applyFilters($query, $filters, $filterRules);

            if ($request->has('search')) {
                $query = QueryFilterSearch::applySearch($query, $request->input('search'), [
                    'grand_total',
                    'transaction_statuses.label'
                ]);
            }

            $result = QueryFilterSearch::applyPagination($query, $request);

            if ($result->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai dengan filter atau pencarian.'
                    ),
                    Response::HTTP_OK
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data detail hewan berhasil didapatkan.',
                    QueryFilterSearch::formatPaginationCollection($result, TransactionResource::class)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('transaction_admin')->error('| Index | - Error function index : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'ERROR_GET_DATA',
                    'Gagal Mengambil Data',
                    'Terjadi kesalahan pada sistem, silahkan coba lagi nanti atau hubungi admin.',
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show($id)
    {
        try {
            if (!Gate::allows('masterdata.view')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            $transaction = Transaction::withTrashed()
                ->with([
                    'users',
                    'order_details',
                    'payment_details',
                    'transaction_statuses'
                ])->find($id);
            if (!$transaction) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Detail transaksi dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $transactionStatus = TransactionStatus::find($transaction->transaction_status_id);
            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail data transaksi dengan status '{$transactionStatus->label}' berhasil didapatkan.",
                    new TransactionResource($transaction)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('transaction_admin')->error('| Detail | - Error function show : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'ERROR_GET_DATA',
                    'Gagal Mengambil Data',
                    'Terjadi kesalahan pada sistem, silahkan coba lagi nanti atau hubungi admin.',
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
