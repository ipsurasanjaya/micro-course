<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Course;
use App\Mentor;
use App\Review;
use App\MyCourse;
use App\Chapter;

class CourseController extends Controller
{

    public function index(Request $request)
    {
        $courses = Course::query();
        
        $q = $request->query('q');
        $status = $request->query('status');

        $courses->when($q, function($query) use ($q) {
            return $query->whereRaw("name LIKE '%".strtolower($q)."%'");
        });

        $courses->when($status, function($query) use ($status) {
            return $query->where('status', '=', $status);
        });

        
        return response()->json([
            'status' => 'success',
            'data' => $courses->paginate(10)
        ]);

            
    }

    public function show($id)
    {
        $course = Course::with(['chapters.lessons', 'mentor', 'images'])->find($id);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ],404);
        }

        $reviews = Review::where('course_id', '=', $id)->get()->toArray();

        if(count($reviews) > 0 ){
            //get user_id from Review table ($reviews -> reviews variable)
            $userIds = array_column($reviews, 'user_id');

            //get users data from User Services, by userIds variable
            $users = getUserByIds($userIds);

            //if users error, ex: User Services down. Return null array
            if($users['status'] === 'error'){
               $reviews =  []; 
            }else {
                foreach($reviews as $key => $review){

                    $userIndex = array_search($review['user_id'], array_column($users['data'], 'id'));

                    $reviews[$key]['users'] = $users['data'][$userIndex];
                }
            }
        }

        $totalStudent = MyCourse::where('course_id', '=', $id)->count();
        $totalVideos = Chapter::where('course_id', '=', $id)->withCount('lessons')->get()->toArray();

        $totalFinalVideos = array_sum(array_column($totalVideos, 'lessons_count'));

        $course['totalStudent'] = $totalStudent;
        $course['reviews'] = $reviews;
        $course['totalVideos'] = $totalFinalVideos;

        return response()->json([
            'status' => 'success',
            'data' => $course
        ]);
    }

    public function create(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'certificate' => 'required|boolean',
            'thumbnail' => 'string|url',
            'type' => 'required|in:free,premium',
            'status' => 'required|in:draft,published',
            'price' => 'integer',
            'level' => 'required|in:all-level,beginner,intermediate,advanced',
            'mentor_id' => 'required|integer',
            'description' => 'string'
        ];

        //request all data from body

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        //if error exist in $validator return error status 404
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 404);
        }

        //if there's no error, check mentor_id exist on database

        //first take mentor_id value from body
        $mentorId = $request->input('mentor_id');

        $mentor = Mentor::find($mentorId);

        if (!$mentor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mentor not found'
            ], 404);
        }

        //create database if there's no error found

        $course = Course::create($data);

        return response()->json([
            'status' => 'succes',
            'data' => $course
        ]);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'string',
            'certificate' => 'boolean',
            'thumbnail' => 'string|url',
            'type' => 'in:free,premium',
            'status' => 'in:draft,published',
            'price' => 'integer',
            'level' => 'in:all-level,beginner,intermediate,advanced',
            'mentor_id' => 'integer',
            'description' => 'string'
        ];

        //request all data from body

        $data = $request->all();

        $validator = Validator::make($data, $rules);

        //if error exist in $validator return error status 404
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 404);
        }

        $course = Course::find($id);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);
        }

	$course->fill($data);
        $course->save();

        return response()->json([
            'status' => 'success',
            'data' => $course
        ]);
    }

    public function destroy($id)
    {
        $course = Course::find($id);

        if(!$course){
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);
        }

        $course->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Course deleted'
        ]);
    }
}
