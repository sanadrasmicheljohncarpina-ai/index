<?php
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();
require_once 'db.php';
header('Content-Type: application/json');

function fail_json(string $message,int $status=400): void { http_response_code($status); echo json_encode(['ok'=>false,'error'=>$message]); exit; }

$role = $_SESSION['role'] ?? '';
if (empty($_SESSION['user_id']) || !in_array($role,['dean','principal'],true)) fail_json('Not authorized.',401);
$userId=(int)$_SESSION['user_id'];
$trackerId=isset($_GET['tracker_id'])?(int)$_GET['tracker_id']:0;
if($trackerId<=0) fail_json('Missing or invalid evaluation.');

$stmt=$mysqli->prepare("
 SELECT et.id, et.remarks, et.submitted_at, et.target_user_id, et.period_id,
        ep.period_label, ep.semester, ep.school_year,
        u.full_name AS target_name
 FROM evaluation_tracker et
 LEFT JOIN evaluation_periods ep ON ep.id=et.period_id
 INNER JOIN users u ON u.id=et.target_user_id
 WHERE et.id=? AND et.target_user_id=?
   AND et.eval_type='student' AND et.evaluation_context='school_head'
   AND et.status IN ('submitted','approved')
 LIMIT 1
");
if(!$stmt) fail_json('Unable to load this evaluation.',500);
$stmt->bind_param('ii',$trackerId,$userId);
$stmt->execute();
$tracker=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$tracker) fail_json('This evaluation could not be found.',404);

$periodLabel = (!empty($tracker['school_year']) && !empty($tracker['semester'])) ? ($tracker['school_year'].' · '.$tracker['semester']) : ($tracker['period_label'] ?? $tracker['semester'] ?? null);

$qStmt=$mysqli->prepare("
 SELECT qa.user_question_id, qa.question_id, qa.question_source, qa.answer_score,
        COALESCE(uq.question_text, eq.question_text) AS question_text,
        COALESCE(uq.category, eq.category, 'General') AS category
 FROM questionnaire_answers qa
 LEFT JOIN user_questions uq ON qa.question_source='user' AND uq.id=qa.user_question_id
 LEFT JOIN evaluation_questions eq ON qa.question_source='evaluation' AND eq.id=qa.question_id
 WHERE qa.tracker_id=?
 ORDER BY category ASC, COALESCE(uq.sort_order, eq.id, 0) ASC, qa.id ASC
");
if(!$qStmt) fail_json('Unable to load evaluation answers.',500);
$qStmt->bind_param('i',$trackerId);
$qStmt->execute();
$res=$qStmt->get_result();
$questions=[];$cats=[];
while($row=$res->fetch_assoc()){
 $score=$row['answer_score']!==null?(float)$row['answer_score']:0.0;
 $cat=$row['category']?:'General';
 $questions[]=['category'=>$cat,'question_text'=>$row['question_text']?:'(This question is no longer available)','score'=>$score];
 if(!isset($cats[$cat]))$cats[$cat]=['sum'=>0.0,'count'=>0];
 $cats[$cat]['sum']+=$score;$cats[$cat]['count']++;
}
$qStmt->close();
$categories=[];$sum=0.0;$count=0;
foreach($cats as $cat=>$v){$categories[]=['category'=>$cat,'avg'=>$v['count']?round($v['sum']/$v['count'],2):0.0];$sum+=$v['sum'];$count+=$v['count'];}
$overall=$count?round($sum/$count,2):0.0;

echo json_encode([
 'ok'=>true,
 'target_name'=>$tracker['target_name'],
 'period_label'=>$periodLabel,
 'submitted_at'=>$tracker['submitted_at']?date('F j, Y g:i A',strtotime($tracker['submitted_at'])):null,
 'overall_score'=>$overall,
 'categories'=>$categories,
 'questions'=>$questions,
 'comment'=>trim((string)($tracker['remarks']??''))!==''?$tracker['remarks']:null
]);
$mysqli->close();
