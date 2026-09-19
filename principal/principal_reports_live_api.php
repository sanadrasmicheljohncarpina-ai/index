<?php
session_start();
require_once 'db.php';
require_once '../shared/system_settings_service.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principal') { http_response_code(401); echo json_encode(['error'=>'unauthenticated']); exit; }
$settings=get_system_settings($mysqli);
$periodId=(int)($settings['period_id']??0);
$activeEval=$_GET['eval_type']??'student'; if(!in_array($activeEval,['student','peer'],true))$activeEval='student';
$group=$_GET['group']??'All'; $meId=(int)$_SESSION['user_id'];
$me=$mysqli->query("SELECT education_level FROM users WHERE id={$meId} LIMIT 1")->fetch_assoc()?:[]; $lvl=$me['education_level']??'both';
if($lvl==='junior_high'){ $grades="'7','8','9','10','Grade 7','Grade 8','Grade 9','Grade 10','Grade 7 - JHS','Grade 8 - JHS','Grade 9 - JHS','Grade 10 - JHS'"; $academic="'junior_high'"; }
elseif($lvl==='senior_high'){ $grades="'11','12','Grade 11','Grade 12','Grade 11 - SHS','Grade 12 - SHS'"; $academic="'senior_high'"; }
else{ $grades="'7','8','9','10','11','12','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','Grade 7 - JHS','Grade 8 - JHS','Grade 9 - JHS','Grade 10 - JHS','Grade 11 - SHS','Grade 12 - SHS'"; $academic="'junior_high','senior_high'"; }
$targetScope="((u.role='staff') OR (u.role IN ('teacher','faculty') AND (u.academic_level IN ($academic) OR EXISTS (SELECT 1 FROM user_year_levels uyl WHERE uyl.user_id=u.id AND uyl.year_level IN ($grades))))) AND u.role NOT IN ('principal','dean','superadmin') AND u.id <> {$meId}";
if($activeEval==='student'){
    $studentEval="et.eval_type='student' AND COALESCE(et.evaluation_context,'teacher') IN ('teacher','staff')";
    switch($group){
        case 'Faculty': case 'Teacher': $groupClause="u.role IN ('teacher','faculty')"; break;
        case 'Staff': $groupClause="u.role='staff'"; break;
        case 'MultiRole': case 'MultiRoleTeacher': case 'MultiRoleStaff': $studentEval="et.eval_type='student' AND (et.evaluation_context='multi_role' OR EXISTS (SELECT 1 FROM questionnaire_answers qam JOIN user_questions uqm ON uqm.id=qam.user_question_id WHERE qam.tracker_id=et.id AND uqm.target_type='Multi-Role' AND uqm.eval_type='student'))"; $groupClause="1=1"; break;
        default: $groupClause="1=1"; break;
    }
    // Student evaluator must belong to the Principal's Basic Education scope.
    $evaluatorScope="EXISTS (SELECT 1 FROM users ev WHERE ev.id=COALESCE(et.evaluator_id,et.student_id) AND ev.role='student' AND ev.is_active=1 AND ev.account_status='approved' AND (ev.grade_level IN ($grades) OR ev.education_level IN ('basic_education','jhs','shs','junior_high','senior_high')))";
    $evalClause="$studentEval AND $evaluatorScope";
}else{
    switch($group){ case 'Faculty': case 'Teacher': $groupClause="u.role IN ('teacher','faculty')"; break; case 'Staff': $groupClause="u.role='staff'"; break; default: $groupClause="1=1"; }
    $evalClause="et.eval_type IN ('peer','faculty_peer','staff_peer')";
}
if($periodId<=0){ echo json_encode(['signature'=>'no-period','period_id'=>0,'total_responses'=>0,'evaluated_targets'=>0,'latest_submission'=>null,'generated_at'=>date('c')]); exit; }
$sql="SELECT COUNT(DISTINCT et.id) total_responses, COUNT(DISTINCT et.target_user_id) evaluated_targets, COALESCE(MAX(et.id),0) max_tracker_id, MAX(et.submitted_at) latest_submission, COUNT(qa.id) answer_count, COALESCE(SUM(qa.answer_score),0) score_sum FROM evaluation_tracker et JOIN users u ON u.id=et.target_user_id LEFT JOIN questionnaire_answers qa ON qa.tracker_id=et.id WHERE et.period_id={$periodId} AND et.submitted_at IS NOT NULL AND {$evalClause} AND {$groupClause} AND {$targetScope}";
$res=$mysqli->query($sql); $row=$res?($res->fetch_assoc()?:[]):[];
$sig=implode('|',[(int)($row['total_responses']??0),(int)($row['evaluated_targets']??0),(int)($row['max_tracker_id']??0),(string)($row['latest_submission']??''),(int)($row['answer_count']??0),number_format((float)($row['score_sum']??0),4,'.','')]);
$mysqli->close(); echo json_encode(['signature'=>$sig,'period_id'=>$periodId,'total_responses'=>(int)($row['total_responses']??0),'evaluated_targets'=>(int)($row['evaluated_targets']??0),'latest_submission'=>$row['latest_submission']??null,'generated_at'=>date('c')]);
