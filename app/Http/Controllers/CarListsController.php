<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BaseAvto;

class CarListsController extends Controller
{
    // Получаем уникальные модели для данной марки
    public function getModels(Request $request)
    {
        $brand = $request->input('brand');
        $models = BaseAvto::where('brand', $brand)->distinct()->pluck('model');

        return response()->json($models);
    }

    // Получаем уникальные годы для данной модели
    public function getYears(Request $request)
    {
        $brand = $request->input('brand');
        $model = $request->input('model');

        // Проверяем, что марка и модель переданы
        if (!$brand || !$model) {
            return response()->json([], 400); // Возвращаем пустой массив с ошибкой 400, если данные не переданы
        }

        // Получаем уникальные годы для данной модели
        $years = BaseAvto::where('brand', $brand)
                         ->where('model', $model)
                         ->select('year_from', 'year_before')
                         ->distinct()
                         ->get();

        $yearList = [];

        foreach ($years as $year) {
            for ($y = $year->year_from; $y <= $year->year_before; $y++) {
                $yearList[] = $y;
            }
        }

        return response()->json(array_unique($yearList));
    }

    // Получаем уникальные модификации для данной модели
    public function getModifications(Request $request)
    {
        $brand = $request->input('brand');
        $model = $request->input('model');
        $year = $request->input('year');

        // Проверяем, что марка, модель и год переданы
        if (!$brand || !$model || !$year) {
            return response()->json([], 400); // Возвращаем пустой массив с ошибкой 400, если данные не переданы
        }

        // Получаем модификации для данной марки, модели и года
        $modifications = BaseAvto::where('brand', $brand)
                                  ->where('model', $model)
                                  ->where('year_from', '<=', $year)
                                  ->where('year_before', '>=', $year)
                                  ->distinct()
                                  ->get(['id_modification', 'modification']); // Получаем id_modification и modification

        return response()->json($modifications);
    }

    // Получаем id_modifications
    public function getIdModifications(Request $request)
    {
        $brand = $request->input('brand');
        $model = $request->input('model');
        $year = $request->input('year');
        $modifications = $request->input('modifications'); // Предполагается, что это массив

        // Проверяем, что марка, модель, год и модификации переданы
        if (!$brand || !$model || !$year || empty($modifications)) {
            return response()->json([], 400); // Возвращаем пустой массив с ошибкой 400, если данные не переданы
        }

        // Получаем id_modification для данной марки, модели, года и модификаций
        $idModifications = BaseAvto::where('brand', $brand)
                                    ->where('model', $model)
                                    ->where('year_from', '<=', $year)
                                    ->where('year_before', '>=', $year)
                                    ->whereIn('modification', $modifications) // Используем whereIn для фильтрации по массиву модификаций
                                    ->pluck('id_modification');

        return response()->json(['id_modifications' => $idModifications]);
    }

    // Получаем уникальные марки
    public function getBrands()
    {
        $brands = BaseAvto::distinct()->pluck('brand')->toArray();
        return response()->json($brands);
    }
}