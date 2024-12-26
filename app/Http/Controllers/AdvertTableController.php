<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Advert;
use App\Models\UserQuery;
use App\Models\BaseAvto;
use App\Models\Part;


class AdvertTableController extends Controller
{
    public function getTableData(Request $request, $advertId)
    {
        $advert = Advert::findOrFail($advertId);
    
        // Логика для получения данных таблицы
        $parts = $this->findPartsByProductName($advert->product_name);
    
        $foundPartId = null;
        if ($parts->isNotEmpty()) {
            $foundPartId = $parts->first()->part_id;
        }
    
        $modificationId = $this->findModificationId($advert);
    
        $userQueries = UserQuery::where('id_part', $foundPartId)
            ->where('id_car', $modificationId)
            ->get();
    
        $queryIds = $userQueries->pluck('id_queri')->toArray();
    
        $relatedQueries = UserQuery::whereIn('id_queri', $queryIds)->get();
    
        if ($relatedQueries->isEmpty()) {
            $adverts = collect();
        } else {
            $adverts = $this->getRelatedCars($relatedQueries)->paginate(10); // 10 элементов на страницу
        }
    
        return response()->json([
            'adverts' => $adverts->items(),
            'pagination' => [
                'current_page' => $adverts->currentPage(),
                'last_page' => $adverts->lastPage(),
                'next_page_url' => $adverts->nextPageUrl(),
                'prev_page_url' => $adverts->previousPageUrl(),
            ]
        ]);
    }

    private function findPartsByProductName($productName)
    {
        return Part::where(Part::raw("'{$productName}'"), 'LIKE', Part::raw("CONCAT('%', part_name, '%')"))->get();
    }

    private function findModificationId($advert)
    {
        $query = BaseAvto::where('brand', $advert->brand)
            ->where('model', $advert->model);
    
        if ($advert->year !== null) {
            $query->where('year_from', '<=', $advert->year)
                  ->where('year_before', '>=', $advert->year);
        }
    
        $baseAvto = $query->first();
    
        return $baseAvto ? $baseAvto->id_modification : null;
    }
    private function getRelatedCars($relatedQueries)
    {
        // Проверяем, что $relatedQueries не пустая
        if ($relatedQueries->isEmpty()) {
            return collect(); // Возвращаем пустую коллекцию, если нет связанных запросов
        }
    
        // Получаем уникальные id_car из связанных запросов
        $carIds = $relatedQueries->pluck('id_car')->unique();
    
        // Возвращаем запрос Eloquent для получения данных из BaseAvto
        return BaseAvto::whereIn('id_modification', $carIds);
    }
}