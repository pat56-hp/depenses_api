<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoriqueRequest;
use App\Http\Resources\HistoriqueResource;
use App\Models\Historique;
use App\Repositories\HistoriqueRepository;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class HistoriqueController extends Controller
{
    private $historiqueRepository;

    public function __construct(HistoriqueRepository $historiqueRepository) {
        $this->middleware('auth:api');
        $this->historiqueRepository = $historiqueRepository;
    }

    /**
     * Recuperation de l'historique en fonction de la date
     */
    public function index(Request $request)
    {
        $date = $request->date ?? now();

        $historique = HistoriqueResource::collection(
            $this->historiqueRepository->getAll($date)
        );

        return response()->json([
            'data' => $historique,
            'solde' => $this->getHistorique($date)['solde'],
            'revenus' => $this->getHistorique($date)['revenus'],
            'depenses' => $this->getHistorique($date)['depenses'],
            'message' => 'Historique récupérés avec succès'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Sauvegarde de l'historique
     */
    public function storeOrUpdate(HistoriqueRequest $request)
    {
        $data = $request->only(
            'libelle', 'description', 'montant', 'date', 'type', 'id'
        );

        try {            
            return response()->json([
                'data' => new HistoriqueResource($this->historiqueRepository->storeOrUpdate($data)),
                'solde' => $this->getHistorique($data['date'])['solde'],
                'revenus' => $this->getHistorique($data['date'])['revenus'],
                'depenses' => $this->getHistorique($data['date'])['depenses'],
                'message' => 'Historique sauvegardé avec succès'
            ], HttpJsonResponse::HTTP_CREATED);
        } catch (\Throwable $th) {
            logger()->error('Erreur lors la sauvegarde de l\'historique : ' . $th->getMessage());
            return response()->json([
               'message' => 'Erreur lors de la sauvegarde de l\'historique',
                'error' => $th->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Suppression d'une donnee
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $this->historiqueRepository->delete($id);
            DB::commit();
            return response()->json([
                'message' => "Donnée supprimée avec succès"
            ], JsonResponse::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollback();
            logger()->error('Erreur lors la suppression de l\'historique : ' . $th->getMessage());
            return response()->json([
               'message' => 'Erreur lors de la suppression de l\'historique',
                'error' => $th->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //Statistiques
    private function getHistorique($date){
        return [
            'solde' => auth('api')->user()->historiques()->whereDate('date', $date)->sum('montant'),
            'revenus' => auth('api')->user()->historiques()->whereDate('date', $date)->where('type', 0)->sum('montant'),
            'depenses' => auth('api')->user()->historiques()->whereDate('date', $date)->where('type', 1)->sum('montant')
        ]; 
    }
}
