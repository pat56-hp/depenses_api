<?php

namespace App\Repositories;

use App\Http\Resources\HistoriqueResource;
use App\Models\Historique;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class HistoriqueRepository {

    protected $historique;
    protected $uploadFile;

    public function __construct(Historique $historique){
        $this->historique = $historique;
    }

    //Recuperation de l'historique du jour
    public function getAll($date){
        $historiques = auth('api')->user()->historiques()
        ->whereDate('date', $date)
        ->latest()
        ->paginate(30);

        return $historiques;
    }

    //Enregistrement ou modification d'un historique
    public function storeOrUpdate($data){
        DB::beginTransaction();
        try {
            $historique = auth('api')->user()->historiques()->updateOrCreate(
                ['user_id' => auth('api')->id(), 'id' => $data['id'] ?? null], // Conditions de recherche
                [
                    'libelle' => $data['libelle'],
                    'montant' => $data['montant'],
                    'description' => $data['description'],
                    'date' => $data['date'],
                    'type' => $data['type'],
                ]
            );
            
            /* if (is_null($id)) {
                $historique = auth('api')->user()->historiques()->create([
                    'libelle' => $data['libelle'],
                    'montant' => $data['montant'],
                    'description' => $data['description'],
                    'date' => $data['date'],
                    'type' => $data['type'],
                ]);
            }else{
                $historique = $this->find($id);
                $historique->update([
                    'libelle' => $data['libelle'],
                    'montant' => $data['montant'],
                    'description' => $data['description'],
                    'date' => $data['date'],
                    'type' => $data['type'],
                ]);
            } */

            DB::commit();
            return $historique;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }
    }

    //Retrouver un historique a partir de L'ID
    public function find($id){
        return auth('api')->user()->historiques()
            ->findOrFail($id);
    }

    //Suppression d'un historique
    public function delete($id){
        $historique = $this->find($id);
        $historique->delete();
    }
}