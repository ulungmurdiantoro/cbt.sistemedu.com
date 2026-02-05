<?php

use Illuminate\Support\Facades\Route;

//prefix "admin"
Route::prefix('admin')->group(function() {

    //middleware "auth"
    Route::group(['middleware' => ['auth']], function () {

        //route dashboard
        Route::get('/dashboard', App\Http\Controllers\Admin\DashboardController::class)->name('admin.dashboard');
    
        //route resource lessons    
        Route::resource('/lessons', \App\Http\Controllers\Admin\LessonController::class, ['as' => 'admin']);

        //route resource classrooms    
        Route::resource('/classrooms', \App\Http\Controllers\Admin\ClassroomController::class, ['as' => 'admin']);
        
        //route student import
        Route::get('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('admin.students.import');

        //route student store import
        Route::post('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'storeImport'])->name('admin.students.storeImport');

        //route resource students    
        Route::resource('/students', \App\Http\Controllers\Admin\StudentController::class, ['as' => 'admin']);
    
        //route resource exams    
        Route::resource('/exams', \App\Http\Controllers\Admin\ExamController::class, ['as' => 'admin']);
        
        //custom route for create question exam
        Route::get('/exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamController::class, 'createQuestion'])->name('admin.exams.createQuestion');

        //custom route for store question exam
        Route::post('/exams/{exam}/questions/store', [\App\Http\Controllers\Admin\ExamController::class, 'storeQuestion'])->name('admin.exams.storeQuestion');
    
        //custom route for edit question exam
        Route::get('/exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamController::class, 'editQuestion'])->name('admin.exams.editQuestion');

        //custom route for update question exam
        Route::put('/exams/{exam}/questions/{question}/update', [\App\Http\Controllers\Admin\ExamController::class, 'updateQuestion'])->name('admin.exams.updateQuestion');
    
        //custom route for destroy question exam
        Route::delete('/exams/{exam}/questions/{question}/destroy', [\App\Http\Controllers\Admin\ExamController::class, 'destroyQuestion'])->name('admin.exams.destroyQuestion');
    
        //route student import
        Route::get('/exams/{exam}/questions/import', [\App\Http\Controllers\Admin\ExamController::class, 'import'])->name('admin.exam.questionImport');

        //route student import
        Route::post('/exams/{exam}/questions/import', [\App\Http\Controllers\Admin\ExamController::class, 'storeImport'])->name('admin.exam.questionStoreImport');
    
        //custom route for create essays exam
        Route::get('/exams/{exam}/essays/create', [\App\Http\Controllers\Admin\ExamController::class, 'createEssay'])->name('admin.exams.createEssay');

        //custom route for store essays exam
        Route::post('/exams/{exam}/essays/store', [\App\Http\Controllers\Admin\ExamController::class, 'storeEssay'])->name('admin.exams.storeEssay');
    
        //custom route for edit essays exam
        Route::get('/exams/{exam}/essays/{essays}/edit', [\App\Http\Controllers\Admin\ExamController::class, 'editEssay'])->name('admin.exams.editEssay');

        //custom route for update essays exam
        Route::put('/exams/{exam}/essays/{essays}/update', [\App\Http\Controllers\Admin\ExamController::class, 'updateEssay'])->name('admin.exams.updateEssay');
    
        //custom route for destroy essays exam
        Route::delete('/exams/{exam}/essays/{essays}/destroy', [\App\Http\Controllers\Admin\ExamController::class, 'destroyEssay'])->name('admin.exams.destroyEssay');
    
        //route student import
        Route::get('/exams/{exam}/essays/import', [\App\Http\Controllers\Admin\ExamController::class, 'import'])->name('admin.exam.essaysImport');

        //route student import
        Route::post('/exams/{exam}/essays/import', [\App\Http\Controllers\Admin\ExamController::class, 'storeImport'])->name('admin.exam.essaysStoreImport');
    
        //route resource exam_sessions    
        Route::resource('/exam_sessions', \App\Http\Controllers\Admin\ExamSessionController::class, ['as' => 'admin']);
    
        //custom route for enrolle create
        Route::get('/exam_sessions/{exam_session}/enrolle/create', [\App\Http\Controllers\Admin\ExamSessionController::class, 'createEnrolle'])->name('admin.exam_sessions.createEnrolle');

        //custom route for enrolle store
        Route::post('/exam_sessions/{exam_session}/enrolle/store', [\App\Http\Controllers\Admin\ExamSessionController::class, 'storeEnrolle'])->name('admin.exam_sessions.storeEnrolle');
        
        //custom route for enrolle destroy
        Route::delete('/exam_sessions/{exam_session}/enrolle/{exam_group}/destroy', [\App\Http\Controllers\Admin\ExamSessionController::class, 'destroyEnrolle'])->name('admin.exam_sessions.destroyEnrolle');

        //route index reports
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');
        
        //route index reports filter
        Route::get('/reports/filter', [\App\Http\Controllers\Admin\ReportController::class, 'filter'])->name('admin.reports.filter');

        //route index reports export
        Route::get('/reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('admin.reports.export');

        //route index reports export pdf
        Route::get('/reports/export-pdf', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('admin.reports.export-pdf');

        //route index reports export pdf
        Route::get('/reports/export-pdf-2', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf2'])->name('admin.reports.export-pdf-2');

        //route essay show
        Route::get('/reports/{id}', [App\Http\Controllers\Admin\ReportController::class, 'show'])->name('admin.reports.show');
        
        //route essay show
        Route::get('/reports/essays/{id}', [App\Http\Controllers\Admin\ReportController::class, 'essayShow'])->name('admin.reports.essayShow');
            
    });
});

//route homepage
Route::get('/', function () {

    //cek session student
    if(auth()->guard('student')->check()) {
        return redirect()->route('student.dashboard');
    }

    //return view login
    return \Inertia\Inertia::render('Student/Login/Index');
});

//login students
Route::post('/students/login', \App\Http\Controllers\Student\LoginController::class)->name('student.login');

//prefix "student"
Route::prefix('student')->group(function() {

    //middleware "student"
    Route::group(['middleware' => 'student'], function () {
        
        //route dashboard
        Route::get('/dashboard', App\Http\Controllers\Student\DashboardController::class)->name('student.dashboard');
    
        //route exam confirmation
        Route::get('/exam-confirmation/{id}', [App\Http\Controllers\Student\ExamController::class, 'confirmation'])->name('student.exams.confirmation');
    
        //route exam start
        Route::get('/exam-start/{id}', [App\Http\Controllers\Student\ExamController::class, 'startExam'])->name('student.exams.startExam');
        
        //route exam show
        Route::get('/exam/{id}/{page}', [App\Http\Controllers\Student\ExamController::class, 'show'])->name('student.exams.show');
    
        //route exam update duration
        Route::put('/exam-duration/update/{grade_id}', [App\Http\Controllers\Student\ExamController::class, 'updateDuration'])->name('student.exams.update_duration');
        
        //route answer question
        Route::post('/exam-answer', [App\Http\Controllers\Student\ExamController::class, 'answerQuestion'])->name('student.exams.answerQuestion');
        
        //route exam end
        Route::post('/exam-end', [App\Http\Controllers\Student\ExamController::class, 'endExam'])->name('student.exams.endExam');
        
        //route exam result
        Route::get('/exam-result/{exam_group_id}', [App\Http\Controllers\Student\ExamController::class, 'resultExam'])->name('student.exams.resultExam');

        //route essay confirmation
        Route::get('/essay-confirmation/{id}', [App\Http\Controllers\Student\EssayController::class, 'confirmation'])->name('student.essays.confirmation');
    
        //route essay start
        Route::get('/essay-start/{id}', [App\Http\Controllers\Student\EssayController::class, 'startEssay'])->name('student.essays.startEssay');
        
        //route essay show
        Route::get('/essay/{id}/{page}', [App\Http\Controllers\Student\EssayController::class, 'show'])->name('student.essays.show');
    
        //route essay update duration
        Route::put('/essay-duration/update/{grade_id}', [App\Http\Controllers\Student\EssayController::class, 'updateDuration'])->name('student.essays.update_duration');
        
        //route answer question
        Route::post('/essay-answer', [App\Http\Controllers\Student\EssayController::class, 'answerQuestion'])->name('student.essays.answerQuestion');
        
        //route essay end
        Route::post('/essay-end', [App\Http\Controllers\Student\EssayController::class, 'endEssay'])->name('student.essays.endEssay');
        
        //route essay result
        Route::get('/essay-result/{essay_group_id}', [App\Http\Controllers\Student\EssayController::class, 'resultEssay'])->name('student.essays.resultEssay');

        // 1) Confirmation
    Route::get('/essay-migas-confirmation/{id}', [App\Http\Controllers\Student\EssayMigasController::class, 'confirmation'])
        ->name('student.essaysmigas.confirmation');

    // 2) Start (generate AnswerEssay + set start_time)
    Route::get('/essay-migas-start/{id}', [App\Http\Controllers\Student\EssayMigasController::class, 'startEssay'])
        ->name('student.essaysmigas.startEssay');

    // 3) Show (soal + upload 1 file jika kamu taruh di halaman show)
    Route::get('/essay-migas/{id}/{page}', [App\Http\Controllers\Student\EssayMigasController::class, 'show'])
        ->name('student.essaysmigas.show');

    // 4) Update duration (AJAX)
    Route::put('/essay-migas-duration/update/{grade_id}', [App\Http\Controllers\Student\EssayMigasController::class, 'updateDuration'])
        ->name('student.essaysmigas.update_duration');

    // 5) Upload page (kalau kamu pakai halaman khusus upload)
    Route::get('/essay-migas/upload/{exam_id}/{exam_session_id}', [App\Http\Controllers\Student\EssayMigasController::class, 'showUploadPage'])
        ->name('student.essaysmigas.uploadPage');

    // 6) Upload file jawaban (API)
    Route::post('/essay-migas-answer', [App\Http\Controllers\Student\EssayMigasController::class, 'answerQuestion'])
        ->name('student.essaysmigas.answerQuestion');

    // 7) Download (kalau masih dipakai)
    Route::get('/essay-migas-download/{exam_id}/{exam_session_id}', [App\Http\Controllers\Student\EssayMigasController::class, 'download'])
        ->name('student.essaysmigas.download');

    // 8) End essay (WAJIB kirim exam_group_id dari FE)
    Route::post('/essay-migas-end', [App\Http\Controllers\Student\EssayMigasController::class, 'endEssay'])
        ->name('student.essaysmigas.endEssay');

    // 9) Result
    Route::get('/essay-migas-result/{essay_group_id}', [App\Http\Controllers\Student\EssayMigasController::class, 'resultEssay'])
        ->name('student.essaysmigas.resultEssay');

    });

});