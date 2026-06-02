<?php

use Illuminate\Support\Facades\Route;

//prefix "admin"
Route::prefix('admin')->group(function() {

    //middleware "auth" + "admin" (role check)
    Route::group(['middleware' => ['auth', 'admin']], function () {

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
        Route::get('/exams/{exam}/essays/import', [\App\Http\Controllers\Admin\ExamController::class, 'EssayImport'])->name('admin.exam.essaysImport');

        //route student import
        Route::post('/exams/{exam}/essays/import', [\App\Http\Controllers\Admin\ExamController::class, 'EssayStoreImport'])->name('admin.exam.essaysStoreImport');
    
        //route resource exam_sessions    
        Route::resource('/exam_sessions', \App\Http\Controllers\Admin\ExamSessionController::class, ['as' => 'admin']);
    
        //custom route for enrolle create
        Route::get('/exam_sessions/{exam_session}/enrolle/create', [\App\Http\Controllers\Admin\ExamSessionController::class, 'createEnrolle'])->name('admin.exam_sessions.createEnrolle');

        //custom route for enrolle store
        Route::post('/exam_sessions/{exam_session}/enrolle/store', [\App\Http\Controllers\Admin\ExamSessionController::class, 'storeEnrolle'])->name('admin.exam_sessions.storeEnrolle');
        
        //custom route for enrolle destroy
        Route::delete('/exam_sessions/{exam_session}/enrolle/{student}/destroy', [\App\Http\Controllers\Admin\ExamSessionController::class, 'destroyEnrolle'])->name('admin.exam_sessions.destroyEnrolle');

        //route classroom document requirements
        Route::get('/classrooms/{classroom}/requirements', [\App\Http\Controllers\Admin\ClassroomRequirementController::class, 'index'])->name('admin.classrooms.requirements');
        Route::post('/classrooms/{classroom}/requirements', [\App\Http\Controllers\Admin\ClassroomRequirementController::class, 'store'])->name('admin.classrooms.requirements.store');
        Route::put('/classrooms/{classroom}/requirements/{requirement}', [\App\Http\Controllers\Admin\ClassroomRequirementController::class, 'update'])->name('admin.classrooms.requirements.update');
        Route::delete('/classrooms/{classroom}/requirements/{requirement}', [\App\Http\Controllers\Admin\ClassroomRequirementController::class, 'destroy'])->name('admin.classrooms.requirements.destroy');

        //route classroom competency units
        Route::get('/classrooms/{classroom}/competency-units', [\App\Http\Controllers\Admin\ClassroomCompetencyUnitController::class, 'index'])->name('admin.classrooms.competency-units');
        Route::post('/classrooms/{classroom}/competency-units', [\App\Http\Controllers\Admin\ClassroomCompetencyUnitController::class, 'store'])->name('admin.classrooms.competency-units.store');
        Route::put('/classrooms/{classroom}/competency-units/{unit}', [\App\Http\Controllers\Admin\ClassroomCompetencyUnitController::class, 'update'])->name('admin.classrooms.competency-units.update');
        Route::delete('/classrooms/{classroom}/competency-units/{unit}', [\App\Http\Controllers\Admin\ClassroomCompetencyUnitController::class, 'destroy'])->name('admin.classrooms.competency-units.destroy');

        //route assessment applications
        Route::get('/applications', [\App\Http\Controllers\Admin\ApplicationController::class, 'index'])->name('admin.applications.index');
        Route::get('/applications/{application}', [\App\Http\Controllers\Admin\ApplicationController::class, 'show'])->name('admin.applications.show');
        Route::post('/applications/{application}/approve', [\App\Http\Controllers\Admin\ApplicationController::class, 'approve'])->name('admin.applications.approve');
        Route::post('/applications/{application}/reject', [\App\Http\Controllers\Admin\ApplicationController::class, 'reject'])->name('admin.applications.reject');
        Route::post('/applications/{application}/reissue', [\App\Http\Controllers\Admin\ApplicationController::class, 'reissueStudent'])->name('admin.applications.reissue');
        Route::post('/applications/{application}/documents/{doc}/verify', [\App\Http\Controllers\Admin\ApplicationController::class, 'verifyDocument'])->name('admin.applications.documents.verify');
        Route::get('/applications/{application}/documents/{document}/download', [\App\Http\Controllers\Admin\ApplicationController::class, 'downloadDocument'])->name('admin.applications.documents.download');
        Route::get('/applications/{application}/tanda-tangan/{type}', [\App\Http\Controllers\Admin\ApplicationController::class, 'serveSignature'])->name('admin.applications.signature.serve');
        Route::get('/profile/tanda-tangan', [\App\Http\Controllers\Admin\ApplicationController::class, 'serveAdminDefaultSignature'])->name('admin.profile.signature');

        // route kelola user (admin & asesor)
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

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

        //route penugasan asesor
        Route::get('/penilaian', [\App\Http\Controllers\Admin\PenilaianController::class, 'index'])->name('admin.penilaian.index');
        Route::get('/penilaian/{exam_session_id}', [\App\Http\Controllers\Admin\PenilaianController::class, 'show'])->name('admin.penilaian.show');
        Route::post('/penilaian/{exam_session_id}/penugasan', [\App\Http\Controllers\Admin\PenilaianController::class, 'saveAssignments'])->name('admin.penilaian.saveAssignments');

        // Grading scheme (komposisi nilai per skema)
        Route::get('/classrooms/{classroom}/grading-scheme', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'show'])->name('admin.classrooms.grading-scheme');
        Route::post('/classrooms/{classroom}/grading-scheme', [\App\Http\Controllers\Admin\GradingSchemeController::class, 'save'])->name('admin.classrooms.grading-scheme.save');

        // Rekap hasil & finalisasi
        Route::get('/results', [\App\Http\Controllers\Admin\ResultController::class, 'index'])->name('admin.results.index');
        Route::get('/results/{examSession}', [\App\Http\Controllers\Admin\ResultController::class, 'show'])->name('admin.results.show');
        Route::post('/results/{examSession}/finalize', [\App\Http\Controllers\Admin\ResultController::class, 'finalize'])->name('admin.results.finalize');
        Route::post('/results/{examSession}/distribute', [\App\Http\Controllers\Admin\ResultController::class, 'distribute'])->name('admin.results.distribute');
        Route::get('/results/{examSession}/download-sk/{student}', [\App\Http\Controllers\Admin\ResultController::class, 'downloadSk'])->name('admin.results.download-sk');
        Route::get('/results/{examSession}/download-sertifikat/{student}', [\App\Http\Controllers\Admin\ResultController::class, 'downloadSertifikat'])->name('admin.results.download-sertifikat');

        // Template dokumen sertifikat
        Route::get('/certificate-template', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'show'])->name('admin.certificate-template');
        Route::post('/certificate-template', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'save'])->name('admin.certificate-template.save');

    });
});

// ─── Portal Asesor ───────────────────────────────────────────────────────────

Route::prefix('asesor')->middleware(['auth', 'asesor'])->group(function () {

    Route::get('/dashboard', \App\Http\Controllers\Asesor\DashboardController::class)->name('asesor.dashboard');

    Route::get('/penilaian/{exam_session_id}/esai', [\App\Http\Controllers\Asesor\EssayAssessmentController::class, 'show'])->name('asesor.esai.show');
    Route::post('/penilaian/{exam_session_id}/esai', [\App\Http\Controllers\Asesor\EssayAssessmentController::class, 'store'])->name('asesor.esai.store');

    Route::get('/penilaian/{exam_session_id}/wawancara', [\App\Http\Controllers\Asesor\InterviewAssessmentController::class, 'show'])->name('asesor.wawancara.show');
    Route::post('/penilaian/{exam_session_id}/wawancara', [\App\Http\Controllers\Asesor\InterviewAssessmentController::class, 'store'])->name('asesor.wawancara.store');

});

//route homepage (login ujian)
Route::get('/', function () {

    //cek session student
    if(auth()->guard('student')->check()) {
        return redirect()->route('student.dashboard');
    }

    //return view login
    return \Inertia\Inertia::render('Student/Login/Index');
});

// ─── Portal Peserta Sertifikasi ─────────────────────────────────────────────

//register & login peserta (public)
Route::get('/peserta/register', [App\Http\Controllers\Peserta\AuthController::class, 'showRegister'])->name('peserta.register');
Route::post('/peserta/register', [App\Http\Controllers\Peserta\AuthController::class, 'register'])->middleware('throttle:5,5');
Route::get('/peserta/login', [App\Http\Controllers\Peserta\AuthController::class, 'showLogin'])->name('peserta.login');
Route::post('/peserta/login', [App\Http\Controllers\Peserta\AuthController::class, 'login'])->name('peserta.login.post')->middleware('throttle:5,1');
Route::post('/peserta/logout', [App\Http\Controllers\Peserta\AuthController::class, 'logout'])->name('peserta.logout');

//reset password peserta (public)
Route::get('/peserta/lupa-password', [App\Http\Controllers\Peserta\PasswordResetController::class, 'showForgot'])->name('peserta.password.request');
Route::post('/peserta/lupa-password', [App\Http\Controllers\Peserta\PasswordResetController::class, 'sendResetLink'])->name('peserta.password.email')->middleware('throttle:3,5');
Route::get('/peserta/reset-password/{token}', [App\Http\Controllers\Peserta\PasswordResetController::class, 'showReset'])->name('peserta.password.reset');
Route::post('/peserta/reset-password', [App\Http\Controllers\Peserta\PasswordResetController::class, 'resetPassword'])->name('peserta.password.update');

//peserta authenticated
Route::prefix('peserta')->middleware('participant')->group(function () {

    //dashboard
    Route::get('/dashboard', App\Http\Controllers\Peserta\DashboardController::class)->name('peserta.dashboard');

    //pilih skema & daftar
    Route::get('/skema', [App\Http\Controllers\Peserta\ApplicationController::class, 'chooseSkema'])->name('peserta.skema.choose');
    Route::post('/skema', [App\Http\Controllers\Peserta\ApplicationController::class, 'storeSkema'])->name('peserta.skema.store');

    //form FR.APL.01
    Route::get('/aplikasi/{application}/form', [App\Http\Controllers\Peserta\ApplicationController::class, 'showForm'])->name('peserta.application.form');
    Route::put('/aplikasi/{application}/form', [App\Http\Controllers\Peserta\ApplicationController::class, 'saveForm'])->name('peserta.application.saveForm');
    Route::post('/aplikasi/{application}/form/tanda-tangan', [App\Http\Controllers\Peserta\ApplicationController::class, 'saveFormSignature'])->name('peserta.application.form.signature');

    //pakta integritas FR.AK.01
    Route::get('/aplikasi/{application}/pakta', [App\Http\Controllers\Peserta\ApplicationController::class, 'showPakta'])->name('peserta.application.pakta');
    Route::post('/aplikasi/{application}/pakta', [App\Http\Controllers\Peserta\ApplicationController::class, 'savePakta'])->name('peserta.application.pakta.save');

    //upload dokumen
    Route::get('/aplikasi/{application}/dokumen', [App\Http\Controllers\Peserta\DocumentController::class, 'index'])->name('peserta.application.documents');
    Route::post('/aplikasi/{application}/dokumen', [App\Http\Controllers\Peserta\DocumentController::class, 'upload'])->name('peserta.application.documents.upload');
    Route::get('/aplikasi/{application}/dokumen/{document}/download', [App\Http\Controllers\Peserta\DocumentController::class, 'download'])->name('peserta.application.documents.download');
    Route::delete('/aplikasi/{application}/dokumen/{document}', [App\Http\Controllers\Peserta\DocumentController::class, 'destroy'])->name('peserta.application.documents.destroy');

    //sajikan tanda tangan secara privat
    Route::get('/aplikasi/{application}/tanda-tangan/{type}', [App\Http\Controllers\Peserta\ApplicationController::class, 'serveSignature'])->name('peserta.application.signature.serve');

    //revisi permohonan yang ditolak
    Route::post('/aplikasi/{application}/revisi', [App\Http\Controllers\Peserta\ApplicationController::class, 'revisi'])->name('peserta.application.revisi');

    //submit permohonan
    Route::post('/aplikasi/{application}/submit', [App\Http\Controllers\Peserta\ApplicationController::class, 'submit'])->name('peserta.application.submit');

    // Hasil & dokumen
    Route::get('/hasil/{sessionId}/{studentId}/sk', [App\Http\Controllers\Peserta\ResultController::class, 'downloadSk'])->name('peserta.hasil.sk');
    Route::get('/hasil/{sessionId}/{studentId}/sertifikat', [App\Http\Controllers\Peserta\ResultController::class, 'downloadSertifikat'])->name('peserta.hasil.sertifikat');
    Route::post('/remidi/{sessionId}', [App\Http\Controllers\Peserta\ResultController::class, 'startRemidi'])->name('peserta.remidi.start');
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


        Route::post('/essay-migas-answer-text', [App\Http\Controllers\Student\EssayMigasController::class, 'storeTextAnswer'])
            ->name('student.essay-migas-answer-text');

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