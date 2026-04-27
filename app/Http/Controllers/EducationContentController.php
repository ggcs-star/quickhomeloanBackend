<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EducationContent;
use App\Models\EducationModule;
use App\Models\Course;
class EducationContentController extends Controller
{
    public function courses()
    {
        $courses = Course::where('status', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    public function modules($course_id)
    {
        $modules = EducationModule::where('course_id', $course_id)
            ->where('status', 1)
            ->with([
                'contents' => function ($q) {
                    $q->where('status', 1);
                }
            ])
            ->orderBy('order')
            ->get();

        $modules->transform(function ($module) {

            $module->total_contents = $module->contents->count();

            $module->total_duration = $module->contents->sum(function ($item) {
                return (int) $item->duration;
            });

            unset($module->contents);

            return $module;
        });

        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    public function contents(Request $request, $module_id)
    {
        $type = $request->type;


        $module = EducationModule::where('status', 1)
            ->find($module_id);

        $query = EducationContent::where('module_id', $module_id)
            ->where('status', 1);

        if ($type) {
            $query->where('type', $type);
        }

        $contents = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'module' => $module,
            'data' => $contents
        ]);
    }
}