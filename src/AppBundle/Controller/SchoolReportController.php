<?php
/*this is the controller for producing reports for the school section*/
namespace AppBundle\Controller;

use AppBundle\Helpers\ExportXLS;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Form\Type\CustomReportType;

class SchoolReportController extends Controller{
    /**
     *@Route("/school/{emisCode}/reports/basic", name="school_reports", requirements={"emisCode":"\d+"})
     */
    public function schoolReportMainAction($emisCode, Request $request){

        //sub-reports to include in the report
        $reports = [0=>"Preliminary counts",1=>"Summary of learners with special needs",2=>"Teaching and learning materials"];
        //available formats for the report
        $formats = [
            'html'=>'html',
            'pdf'=>'pdf',
            'excel'=>'excel'
        ];

        //create the form for choosing which sub-form to include and the format of the fina report
        $form = $this->createFormBuilder()
            ->add('reports','choice', array(
                'label' => 'Include',
                'expanded' => true,
                'multiple' => true,
                'choices'=> $reports,
                'constraints' => array(new NotBlank(["message"=>"Please select atleast one option"])),
            ))
            ->add('format','choice', array(
                'label' => 'Format',
                'expanded' => true,
                'multiple' => false,
                'choices'=> $formats,
                'data' => 0,
                'constraints' => array(new NotBlank(["message"=>"Please select a format"])),
            ))
            ->add('produce','submit', array('label' => "Produce report"))
            ->getForm();

        $form->handleRequest($request);
        if($form->isValid()){
            $connection = $this->get('database_connection');
            $formData = $form->getData();
            $options = array(); //list of options to pass to the template
            $school = $connection->fetchAssoc('SELECT emiscode,school_name,iddistrict,district_name,idzone,zone_name
				FROM school NATURAL JOIN district NATURAL JOIN zone WHERE emiscode = ?', [$emisCode]);

            $options['school'] = $school;
            $options['isSchool'] = true;
            $options['isZone'] = false;
            $options['isDistrict'] = false;
            $options['isNational'] = false;

            $learners = array();
            $dataConverter = $this->get('data_converter');

            $session = $request->getSession();
            $year = '';
            //keep the emiscode of the selected zone in the session so we can always redirect to it until the next school is chosen
            if ($session->has('school_year')){
                $year = $session->get('school_year');
            } else {
                return $this->redirectToRoute('school_reports',['emisCode'=>$emisCode], 301);
            }
            $sntLatestYr['yr'] = $year;
            //$sntLatestYr = $connection->fetchAssoc('SELECT MAX(year) AS yr '
            //. 'FROM school_has_snt NATURAL JOIN school WHERE emiscode = ?',[$emisCode]);
            $sntLastYr = $sntLatestYr['yr'] - 1;
            $lwdLatestYr['yr'] = $year;
            //$lwdLatestYr = $connection->fetchAssoc('SELECT MAX(year) AS yr '
            //. 'FROM lwd_belongs_to_school NATURAL JOIN school WHERE emiscode = ?',[$emisCode]);
            $lwdLastYr = $lwdLatestYr['yr'] - 1;
            $options['chaka'] = $year;
            if(in_array(1, $formData['reports']) || in_array(0, $formData['reports'])){
                $yearQuery['maxYear'] = $year;
                //learner preliminary counts
                $learners = $connection->fetchAll('SELECT DISTINCT(idlwd), sex, std, dob, round(datediff(?,dob)/365) as age
				  FROM lwd NATURAL JOIN lwd_belongs_to_school
					WHERE emiscode = ? AND `year`=?', [$yearQuery['maxYear'].'-01-01', $emisCode, $yearQuery['maxYear']]);

                /*Preliminary counts section*/
                if(in_array(0, $formData['reports'])){ //if the preliminary counts option was checked
                    $options['preliminary'] = true;

                    $options['learnersTotal'] = count($learners);
                    $options['numBoys'] = $dataConverter->countArray($learners, 'sex', "M");//get the number of boys
                    $options['numGirls'] = $options['learnersTotal'] - $options['numBoys'];//get the number of girls

                    //snt preliminary counts
                    $latestYr = $connection->fetchAssoc('SELECT MAX(year) AS yr FROM school_has_snt
                                            WHERE emiscode = ?',[$emisCode]);
                    $teachers = $connection->fetchAll('SELECT * FROM school_has_snt NATURAL JOIN snt
                                            WHERE `year` = ? AND emiscode = ?', [$latestYr['yr'], $emisCode]);
                    $options['sntTotal'] = count($teachers);
                    $options['sntMale'] = $dataConverter->countArray($teachers, 's_sex', 'M');
                    $options['sntFemale'] = $options['sntTotal'] - $options['sntMale'];
                    $options['sntItinerant'] = $dataConverter->countArray($teachers, 'snt_type', 'Itinerant');
                    $options['sntResident'] = $options['sntTotal'] - $options['sntItinerant'];

                    //classroom preliminary counts
                    $classPopulations = $connection->fetchAll('SELECT std, COUNT(DISTINCT(idlwd)) as numLearners FROM lwd_belongs_to_school
                                            NATURAL JOIN lwd WHERE emiscode = ? AND `year` = ? GROUP BY std', [$emisCode, $yearQuery['maxYear']]);
                    $options['maxLearners'] = $dataConverter->findArrayMax($classPopulations, 'numLearners');
                    $options['minLearners'] = $dataConverter->findArrayMin($classPopulations, 'numLearners');

                    //room state preliminary counts
                    $rooms = $connection->fetchAll('SELECT room_id,enough_light,enough_space,enough_ventilation,adaptive_chairs,room_type,`access`, noise_free
                                            FROM room_state WHERE emiscode = ? AND `year` = ?', [$emisCode, $yearQuery['maxYear']]);
                    $options['rmTotal'] = count($rooms);
                    $options['rmEnoughLight'] = $dataConverter->countArray($rooms, 'enough_light', 'yes');
                    $options['rmEnoughSpace'] = $dataConverter->countArray($rooms, 'enough_space', 'yes');
                    $options['rmEnoughVent'] = $dataConverter->countArray($rooms, 'enough_ventilation', 'yes');
                    $options['rmAdaptiveChairs'] = $dataConverter->countArray($rooms, 'adaptive_chairs', 'yes');
                    $options['rmAccessible'] = $dataConverter->countArray($rooms, 'access', 'yes');
                    $options['rmNoiseFree'] = $dataConverter->countArray($rooms, 'noise_free', 'yes');
                    $options['rmTemporary'] = $dataConverter->countArray($rooms, 'room_type', 'Temporary');
                    $options['rmPermanent'] = $dataConverter->countArray($rooms, 'room_type', 'Permanent');
                    $options['rmOpenair'] = $options['rmTotal'] - $options['rmTemporary'] - $options['rmPermanent'];
                }
                /*End of preliminary counts section*/

                /*Summary of learners with special needs section*/
                if(in_array(1, $formData['reports'])){//if the summary of learners with special needs option was checked
                    $options['specialNeeds'] = true;
                    $learnersTransOut = $connection->fetchAll("SELECT
                                        lwd_belongs_to_school.idlwd,
                                        lwd_belongs_to_school.emiscode,
                                        lwd_belongs_to_school.year,
                                        lwd.sex
                                    FROM
                                        lwd
                                        INNER JOIN lwd_belongs_to_school
                                         ON lwd.idlwd = lwd_belongs_to_school.idlwd
                                    WHERE
                                        year = ?
                                        AND emiscode = ?
                                        AND lwd_belongs_to_school.idlwd IN (SELECT
                                                        lwd_belongs_to_school.idlwd
                                                     FROM
                                                        lwd_belongs_to_school
                                                     WHERE
                                                        year = ?
                                                        AND emiscode != ?)", [$lwdLastYr, $emisCode, $lwdLatestYr['yr'], $emisCode]);
                    $learnersTransIn = $connection->fetchAll("SELECT
                                        lwd_belongs_to_school.idlwd,
                                        lwd_belongs_to_school.emiscode,
                                        lwd_belongs_to_school.year,
                                        lwd.sex
                                    FROM
                                        lwd
                                        INNER JOIN lwd_belongs_to_school
                                         ON lwd.idlwd = lwd_belongs_to_school.idlwd
                                    WHERE
                                        year = ?
                                        AND emiscode = ?
                                        AND lwd_belongs_to_school.idlwd IN (SELECT
                                                        lwd_belongs_to_school.idlwd
                                                     FROM
                                                        lwd_belongs_to_school
                                                     WHERE
                                                        year = ?
                                                        AND emiscode != ?)", [$lwdLatestYr['yr'], $emisCode, $lwdLastYr, $emisCode]);
                    //get students enrolled this year
                    $enrolled = $connection->fetchAll('SELECT sex, COUNT(idlwd) as total '
                        . 'FROM lwd NATURAL JOIN lwd_belongs_to_school '
                        . 'WHERE emiscode = ? AND `year` = ? GROUP BY sex',
                        [$emisCode, $yearQuery['maxYear']]);
                    $options['enrolledGirls'] = 0;
                    $options['enrolledBoys'] = 0;
                    foreach ($enrolled as $en){
                        if ($en['sex'] == 'M'){
                            $options['enrolledBoys'] = $en['total'];
                        }else {
                            $options['enrolledGirls'] = $en['total'];
                        }
                    }
                    $options['enrolledTotal'] = $options['enrolledBoys'] + $options['enrolledGirls'];
                    $exitedGrouped = $connection->fetchAll('SELECT COUNT(idlwd), disability_category.* '
                        . 'FROM lwd NATURAL JOIN school_exit NATURAL JOIN lwd_has_disability NATURAL JOIN disability NATURAL JOIN disability_category '
                        . 'WHERE emiscode = ? AND `year` = ? GROUP BY category_name', [$emisCode, $yearQuery['maxYear']]);
                    //get disability category total from exitedGrouped above

                    //get SNE students by category of impairment and gender
                    $disabilitiesDB = $connection->fetchAll('select iddisability, disability_name from disability');
                    $gender = array('M'=>'M','F'=>'F');
                    $disabilities = array();
                    foreach ($disabilitiesDB as $key => $row) {
                        $disabilities[$row['iddisability']] = $row['disability_name'];
                    }
                    $exited = $connection->fetchAll('SELECT disability.disability_name, sex, reason, count(idlwd) as dropouts '
                        . 'FROM lwd NATURAL JOIN school_exit NATURAL JOIN lwd_has_disability '
                        . 'NATURAL JOIN disability NATURAL JOIN school '
                        . 'WHERE emiscode = ? and `year` = ? GROUP BY disability.disability_name, sex, reason'
                        , [$emisCode, $yearQuery['maxYear']]);
                    //get dropouts and completed counts

                    $dCategoryCount = array();//dropouts count
                    $cCategoryCount = array();//completed count
                    $categoryCount = array(); //this is counting disabilities not categories of disbailities anymore
                    $dOrC = array('Dropouts'=>'!= "completed"','Completed STD 8'=>'== "completed"');

                    //initialise array
                    foreach ($disabilities as $catKey => $category) {
                        //$dropoutCategories = $dataConverter->selectFromArrayBool($exited, 'disability_name', '== '.$category);
                        foreach ($dOrC as $dcKey => $dc) {
                            foreach ($gender as $genKey => $gen){
                                $categoryCount[$category][$dcKey][$gen] = 0;
                            }
                        }
                    }

                    foreach ($exited as $ex){//
                        if ($ex['reason'] != 'completed') {
                            $ex['reason'] = 'Dropouts';
                        }else {
                            $ex['reason'] = 'Completed STD 8';
                        }
                        $categoryCount[$ex['disability_name']][$ex['reason']][$ex['sex']] = +$ex['dropouts'];

                    }
                    $options['dOrCsKey'] = $disabilities[1];
                    $options['categoryCounts'] = $categoryCount;
                    //$options['cCategoryCounts'] = $cCategoryCount;

                    //get total number of dropouts
                    $exitedT = $connection->fetchAll('SELECT * FROM `school_exit` NATURAL JOIN school NATURAL JOIN lwd '
                        . 'WHERE school_exit.emiscode = ? and `year` = ?'
                        , [$emisCode, $yearQuery['maxYear']]);
                    $dropoutReason = '!= "completed"';
                    $completedReason = '== "completed"';
                    $dropouts = $dataConverter->selectFromArrayBool($exitedT, 'reason', $dropoutReason, TRUE);
//                                    echo print_r($exitedT); exit;
                    $options['dropoutTotal'] = count($dropouts);
                    $options['dropoutBoys'] = $dataConverter->countArray($dropouts, 'sex', 'M');
                    $options['dropoutGirls'] = $options['dropoutTotal'] - $options['dropoutBoys'];
//                                    echo $options['dropoutTotal']; exit;
                    //get learners completed std 8
                    $completed = $dataConverter->selectFromArrayBool($exitedT, 'reason', $completedReason, TRUE);
                    $options['completedTotal'] = count($completed);
                    $options['completedBoys'] = $dataConverter->countArray($completed, 'sex', 'M');
                    $options['completedGirls'] = $options['completedTotal'] - $options['completedBoys'];

                    //transfers out
                    $options['numBoysTRout'] = $dataConverter->countArray($learnersTransOut, 'sex', 'M');
                    $options['numGirlsTRout'] = $dataConverter->countArray($learnersTransOut, 'sex', 'F');
                    $options['totalTransferOut'] =  $options['numBoysTRout'] + $options['numGirlsTRout'];

                    //transfer in
                    $options['numBoysTRin'] = $dataConverter->countArray($learnersTransIn, 'sex', 'M');
                    $options['numGirlsTRin'] = $dataConverter->countArray($learnersTransIn, 'sex', 'F');
                    $options['totalTransferIn'] =  $options['numBoysTRin'] + $options['numGirlsTRin'];

                    //

                    //lwds by class, age and sex
                    $learnersBySexAgeStd = array();
                    $learnersBy = array();
                    $totalStdSexAge = array();

                    $ages = array('<6'=>5, '6'=>6, '7'=>7,
                        '8'=>8, '9'=>9, '10'=>10,'11'=>11,'12'=>12,
                        '13'=>13,'14'=>14,'15'=>15,'16'=>16,'17'=>17,'>17'=>18);
                    $stds = array('1'=>1, '2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8);
                    $counterStdSex = array();
                    $counterStdBySex = array();

                    //obtain the counter and sums for age by sex
                    foreach ($ages as $key => $age) {
                        $counterAgeBySex[$key]['M'] = 0;
                        $counterAgeBySex[$key]['F'] = 0;
                        foreach ($stds as $std) {
                            foreach ($gender as $sex) {
                                if ($key == '<6'){
                                    $learnersBy[$key][$std][$sex] = $dataConverter->countArrayMultipleBool($learners, ['age'=>$key, 'std'=>' == '.$std, 'sex'=>' == \''.$sex.'\'']);
                                }elseif($key == '>17'){
                                    $learnersBy[$key][$std][$sex] = $dataConverter->countArrayMultipleBool($learners, ['age'=>$key, 'std'=>' == '.$std, 'sex'=>' == \''.$sex.'\'']);
                                }else{
                                    $learnersBy[$key][$std][$sex] = $dataConverter->countArrayMultiple($learners, ['age'=>$age, 'std'=>$std, 'sex'=>$sex]);
                                }
                                //get totals for across age and standards by sex
                                if ($sex == 'M'){
                                    $counterAgeBySex[$key]['M'] = $counterAgeBySex[$key]['M'] + $learnersBy[$key][$std][$sex];
                                }else {
                                    $counterAgeBySex[$key]['F'] = $counterAgeBySex[$key]['F'] + $learnersBy[$key][$std][$sex];
                                }
                            }
                        }
                    }
                    //flip the array to sum downwards for std by sex
                    foreach ($stds as $std) {
                        $counterStdBySex[$std]['M'] = 0;
                        $counterStdBySex[$std]['F'] = 0;
                        foreach ($ages as $key => $age) {
                            foreach ($gender as $sex) {
                                //get totals for across age and standards by sex
                                if ($sex == 'M'){
                                    $counterStdBySex[$std]['M'] =  $counterStdBySex[$std]['M'] + $learnersBy[$key][$std][$sex];
                                }else {
                                    $counterStdBySex[$std]['F'] =  $counterStdBySex[$std]['F'] + $learnersBy[$key][$std][$sex];
                                }
                            }
                        }
                    }
                    $options['stdBySex'] = $counterStdBySex;
                    $options['ageBySex'] = $counterAgeBySex;
                    $options['learnersBy'] = $learnersBy;
                    /* end of lwds by age, sex and std*/
                }
            }
            /*End of Summary of learners with special needs section*/

            /*Start of Teaching and learning materials*/
            if(in_array(2, $formData['reports'])){ //if the Teaching and learning materials option was checked
                $options['learningMaterials'] = true;
                //learners needs - STARTS HERE
                $learnersNeeds = $connection->fetchAll('SELECT emiscode, needname, school_has_need.* '
                    . 'FROM school_has_need NATURAL JOIN school NATURAL JOIN need '
                    . 'WHERE school.emiscode = ? AND school_has_need.year_recorded = ?
                                    AND (need_type="school only" OR need_type="both")', [$emisCode, $lwdLatestYr['yr']]);
                //learners needs by resource room or not - ENDS HERE
                //roor state data starts here
                $learnersRooms = $connection->fetchAll('SELECT * FROM room_state where emiscode = ? and year = ?', [$emisCode, $lwdLatestYr['yr']]);
                $options['teachingNeeds'] = $learnersNeeds;
                $options['teachingRooms'] = $learnersRooms;
            }
            /*End of Teaching and learning materials*/

            $productionDate = new \DateTime(date('Y-m-d H:i:s'));
            $options['date'] = $productionDate;
            if($formData['format'] == 'html' || $formData['format'] == 'pdf'){
                $isHtml = ($formData['format'] == 'html')? true: false;
                $options['isHtml'] = $isHtml;
                $html = $this->renderView('school/reports/aggregate_report.html.twig', $options);
                if($isHtml){
                    return new Response($html);
                }else{
                    $mpdfService = $this->get('tfox.mpdfport');
                    $arguments = ['outputFileName'=>'IED-'.$school['school_name'].'_basic-report-'
                        .date('d-m-Y').'.pdf', 'outputDest'=>"I"];
                    $response = $mpdfService->generatePdfResponse($html, $arguments);
                    $response->headers->set('Content-Disposition','inline; filename = '.$arguments['outputFileName']);
                    $response->headers->set('Content-Transfer-Encoding','binary');
                    $response->headers->set('Accept-Ranges','bytes');
                    return $response;
                    exit;
                }
            }else{
                $xml = $this->renderView('school/reports/aggregate_report.xml.twig', $options);
                $temporary_file_name = $this->getParameter('kernel.cache_dir').'/excel.xml'; //temporary file for storing the xml
                file_put_contents($temporary_file_name, $xml);

                ob_clean();
                $reader = \PHPExcel_IOFactory::createReader('Excel2003XML');
                $excelSheet = $reader->load($temporary_file_name);
                $writer = $this->get('phpexcel')->createWriter($excelSheet, 'Excel2007');

                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                // adding headers
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'IED-'.$school['school_name'].'_basic-report-'.date('d-m-Y').'.xlsx'
                );
                $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;
            }

        }

        return $this->render('school/reports/reports_basic.html.twig', array('form' => $form->createView()));
    }

    /**
     *@Route("/school/{emisCode}/reports/custom", name="school_custom_reports", requirements={"emisCode":"\d+"})
     */
    public function schoolCustomReportAction($emisCode, Request $request){
        //create the form for choosing which sub-form to include and the format of the final report
        $form = $this->createForm(new CustomReportType());

        $form->handleRequest($request);

        if($form->isValid()){
            $connection = $this->get('database_connection');
            $formData = $form->getData();
            $options = array(); //list of options to pass to the template
            $school = $connection->fetchAssoc('SELECT emiscode,school_name,iddistrict,district_name,idzone,zone_name
				FROM school NATURAL JOIN district NATURAL JOIN zone WHERE emiscode = ?', [$emisCode]);

            $options['school'] = $school;
            $options['isSchool'] = true;
            $options['isZone'] = false;
            $options['isDistrict'] = false;
            $options['isNational'] = false;

            $learners = array();
            $dataConverter = $this->get('data_converter');
            //if(in_array(1, $formData['enrollments']) || in_array(0, $formData['enrollments'])){
            $session = $request->getSession();
            $year = '';
            //keep the emiscode of the selected zone in the session so we can always redirect to it until the next school is chosen
            if ($session->has('school_year')){
                $year = $session->get('school_year');
            } else {
                return $this->redirectToRoute('school_reports',['emisCode'=>$emisCode], 301);
            }
            //get the latest year from the lwd_belongs to school table
            //$yearQuery = $connection->fetchAssoc('SELECT MAX(`year`) as maxYear FROM lwd_belongs_to_school
            //        WHERE emiscode = ?', [$emisCode]);
            $yearQuery['maxYear'] = $year;
            $gender = array('M'=>'M','F'=>'F');
            $learnersBySex = array();
            $total = 0;
            $options['chaka'] = $year;

            /*SN learners' details section*/
            if(in_array(0, $formData['reports'])){//if the SN learners' details option was checked
                $options['snLearners'] = true;
                //get students a this year
                $enrolled = $connection->fetchAll('SELECT idlwd, first_name, last_name, home_address, sex, dob,
                            distance_to_school, gfirst_name, glast_name, gsex, occupation, household_type
                            FROM lwd NATURAL JOIN guardian NATURAL JOIN lwd_belongs_to_school NATURAL JOIN school
                            WHERE emiscode = ? AND `year` = ?',
                    [$emisCode, $yearQuery['maxYear']]);
                $options['snLearners'] = $enrolled;
            }
            if(in_array(1, $formData['reports'])){//if the SN teachers' details option was checked
                $options['snTeachers'] = true;
                //get teachers this year
                $employed = $connection->fetchAll('SELECT sfirst_name,slast_name, employment_number,
                                        s_sex, qualification, speciality, year_started, teacher_type
                                        FROM snt NATURAL JOIN school_has_snt NATURAL JOIN school
                                        WHERE emiscode = ? AND `year` = ?',
                    [$emisCode, $yearQuery['maxYear']]);
                $options['snTeachers'] = $employed;
            }

            if(in_array(0, $formData['enrollments'])){//if by class and sex option was checked
                $options['lwdBCG'] = true;
                //get students enrolled this year
                $enrolled = $connection->fetchAll('SELECT idlwd, std, sex, emiscode '
                    . 'FROM lwd NATURAL JOIN lwd_belongs_to_school '
                    . 'WHERE emiscode = ? AND `year` = ?',[$emisCode, $yearQuery['maxYear']]);

                $enrolledCount = $dataConverter->countArray($enrolled, 'emiscode', $emisCode);
                $stds = array('1'=>1, '2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8);

                $learnersByClassSex = array();
                $learnersByClass = array();
                //initialise arrays
                foreach ($gender as $key => $gValue) {
                    foreach ($stds as $key => $std) {
                        $learnersByClassSex[$gValue][$std] = '0';
                        $learnersByClass[$std] = '0';
                    }
                    $learnersBySex[$gValue] = '0';
                }
                //loop through the selection list counting the gender, std combinations
                foreach ($gender as $key => $gValue) {
                    foreach ($stds as $key => $std) {
                        $learnersByClassSex[$gValue][$std] = $dataConverter->countArrayMultiple($enrolled, ['sex'=>$gValue, 'std'=>$std]);
                        $learnersBySex[$gValue] = $learnersBySex[$gValue] + $learnersByClassSex[$gValue][$std];
                    }
                }
                //sum learners per class
                foreach ($stds as $key => $std){
                    foreach ($gender as $key => $gValue){
                        $learnersByClass[$std] = $learnersByClass[$std] + $learnersByClassSex[$gValue][$std];
                    }
                }
                $total = 0;
                //flip and sum per class
                foreach ($stds as $key => $std) {
                    foreach ($gender as $key => $gValue) {
                        //$learnersByClass[$std] = $learnersByClass[$std]
                        $total = $total + $learnersByClassSex[$gValue][$std];
                        //$total = $total + $learnersByClass[$std];
                    }
                }
                $options['learnersBCGT'] = $total;
                $options['learnersBCG'] = $learnersByClassSex;
                $options['learnersBG'] = $learnersBySex;
                $options['learnersBC'] = $learnersByClass;
            }
            if(in_array(1, $formData['enrollments'])){//if disability category and sex was checked
                $options['lwdBIG'] = true;
                $dbCategories = $connection->fetchAll('SELECT iddisability_category, category_name FROM disability_category');
                $categories = array();
                foreach ($dbCategories as $key => $row) {
                    $categories[$row['iddisability_category']] = $row['category_name'];
                }
                //get teachers this year
                $enrolled = $connection->fetchAll('SELECT idlwd, category_name, sex, emiscode '
                    . 'FROM lwd NATURAL JOIN lwd_belongs_to_school NATURAL JOIN lwd_has_disability'
                    . ' NATURAL JOIN disability NATURAL JOIN disability_category'
                    . ' WHERE emiscode = ? AND `year` = ?',[$emisCode, $yearQuery['maxYear']]);

                $learnersByCategorySex = array();
                $learnersByCategory = array();
                //initialise array
                foreach ($categories as $key => $category) {
                    foreach ($gender as $key => $gValue) {
                        $learnersByCategorySex[$category][$gValue] = '0';
                        $learnersBySex[$gValue] = '0';
                    }
                    $learnersByCategory[$category] = '0';
                }
                //loop through the selection list counting the gender, std combinations
                foreach ($categories as $key => $category) {
                    foreach ($gender as $key => $gValue) {
                        $learnersByCategorySex[$category][$gValue] = $dataConverter->countArrayMultiple($enrolled, ['sex'=>$gValue, 'category_name'=>$category]);
                        $learnersByCategory[$category] = $learnersByCategory[$category] + $learnersByCategorySex[$category][$gValue];
                    }
                }
                //sum by sex across the disability categories
                foreach ($gender as $key => $gValue) {
                    foreach ($categories as $key => $category) {
                        $learnersBySex[$gValue] = $learnersBySex[$gValue] + $learnersByCategorySex[$category][$gValue];
                    }
                }
                $total = 0;
                //flip and sum per class
                foreach ($gender as $key => $gValue) {
                    foreach ($categories as $key => $category) {
                        //$learnersBySex[$gValue] = $learnersBySex[$gValue] +
                        $total = $total + $learnersByCategorySex[$category][$gValue];
                    }
                }
                $options['learnersBIGT'] = $total;
                $options['learnersBS1'] = $learnersBySex;
                $options['learnersBC1'] = $learnersByCategory;
                $options['learnersBIG'] = $learnersByCategorySex;
            }
            if(in_array(2, $formData['enrollments'])){//if disability and sex was checked
                $options['lwdBDG'] = true;
                $dbDisabilities = $connection->fetchAll('SELECT iddisability, disability_name FROM disability');
                $disabilities = array();
                foreach ($dbDisabilities as $key => $row) {
                    $disabilities[$row['iddisability']] = $row['disability_name'];
                }
                //get teachers this year
                $enrolled = $connection->fetchAll('SELECT idlwd, disability_name, sex, emiscode '
                    . 'FROM lwd NATURAL JOIN lwd_belongs_to_school NATURAL JOIN lwd_has_disability'
                    . ' NATURAL JOIN disability'
                    . ' WHERE emiscode = ? AND `year` = ?',[$emisCode, $yearQuery['maxYear']]);

                $learnersByDisabilitySex = array();
                $learnersByDisability = array();
                //initialise arrays
                foreach ($disabilities as $key => $disability) {
                    foreach ($gender as $key => $gValue) {
                        $learnersByDisabilitySex[$disability][$gValue] = '0';
                        $learnersBySex[$gValue] = '0';
                    }
                    $learnersByDisability[$disability] = '0';
                }

                //loop through the selection list counting the gender, std combinations
                foreach ($disabilities as $key => $disability) {
                    foreach ($gender as $key => $gValue) {
                        $learnersByDisabilitySex[$disability][$gValue] = $dataConverter->countArrayMultiple($enrolled, ['sex'=>$gValue, 'disability_name'=>$disability]);
                        $learnersByDisability[$disability] = $learnersByDisability[$disability] + $learnersByDisabilitySex[$disability][$gValue];
                    }
                }
                $total = 0;
                //flip and sum per class
                foreach ($gender as $key => $gValue) {
                    foreach ($disabilities as $key => $disability) {
                        $learnersBySex[$gValue] = $learnersBySex[$gValue] + $learnersByDisabilitySex[$disability][$gValue];
                        $total = $total + $learnersByDisabilitySex[$disability][$gValue];
                        //$total = $total + $learnersBySex[$gValue];
                    }
                }
                $options['learnersBDGT'] = $total;
                $options['learnersBS2'] = $learnersBySex;
                $options['learnersBD'] = $learnersByDisability;
                $options['learnersBDG'] = $learnersByDisabilitySex;
            }
            $productionDate = new \DateTime(date('Y-m-d H:i:s'));
            $options['date'] = $productionDate;
            if($formData['format'] == 'html' || $formData['format'] == 'pdf'){
                $isHtml = ($formData['format'] == 'html')? true: false;
                $options['isHtml'] = $isHtml;
                $html = $this->renderView('school/reports/aggregate_custom.html.twig', $options);
                if($isHtml){
                    return new Response($html);
                }else{
                    $mpdfService = $this->get('tfox.mpdfport');
                    $arguments = ['outputFileName'=>'IED-'.$school['school_name'].'_custom-report-'
                        .date('d-m-Y').'.pdf', 'outputDest'=>"I"];
                    $response = $mpdfService->generatePdfResponse($html, $arguments);
                    $response->headers->set('Content-Disposition','inline; filename = '.$arguments['outputFileName']);
                    $response->headers->set('Content-Transfer-Encoding','binary');
                    $response->headers->set('Accept-Ranges','bytes');
                    return $response;
                    exit;
                }
            }else{
                ob_clean();
                $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
                $phpExcelObject->getProperties()->setCreator("Inclusive Education Database")
                    ->setLastModifiedBy("IED")
                    ->setTitle("Inclusive Education Database Report")
                    ->setSubject("IED Custom Report for ".$year)
                    ->setDescription("Excel Report generated by the Inclusive Education Database.")
                    ->setKeywords("report custom inclusive education")
                    ->setCategory("Report File");
                $activeSheetIndex = 0;
                $phpExcelObject->setActiveSheetIndex($activeSheetIndex);
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $columnHeadingStyle = array(
                    'font' => array(
                        'bold' => true
                    )
                );
                if(isset($options['learnersBCG'])){
                    $phpExcelObject->getActiveSheet()->fromArray(array(
                        array('Male'),
                        array('Female'),
                    ), null, 'A2');
                    $phpExcelObject->getActiveSheet()->fromArray(array(
                        'std1','std2','std3','std4','std5','std6','std7','std8'
                    ), null, 'B1');
                    $phpExcelObject->getActiveSheet()->fromArray($options['learnersBCG'], null, 'B2', true);
                    $phpExcelObject->getActiveSheet()->setTitle("Learners by Class & Sex");
                    $maxCell = $phpExcelObject->getActiveSheet()->getHighestRowAndColumn();
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'. $maxCell['column'] . $maxCell['row'])->applyFromArray($styleArray);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'.$maxCell['column'].'1')->applyFromArray($columnHeadingStyle);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:A'.$maxCell['row'])->applyFromArray($columnHeadingStyle);
                    $activeSheetIndex += 1;
                }
                if(isset($options['lwdBIG'])){
                    $phpExcelObject->createSheet();
                    $phpExcelObject->setActiveSheetIndex($activeSheetIndex);
                    foreach($options['learnersBIG'] as $key => &$array){
                        array_unshift($array, $key);
                    }
                    $phpExcelObject->getActiveSheet()->fromArray(array(
                        'Male', 'Female'
                    ), null, 'B1', true);
                    $phpExcelObject->getActiveSheet()->fromArray($options['learnersBIG'], null, 'A2', true);
                    $phpExcelObject->getActiveSheet()->setTitle("Disability Category & Sex");
                    $maxCell = $phpExcelObject->getActiveSheet()->getHighestRowAndColumn();
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'. $maxCell['column'] . $maxCell['row'])->applyFromArray($styleArray);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'.$maxCell['column'].'1')->applyFromArray($columnHeadingStyle);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:A'.$maxCell['row'])->applyFromArray($columnHeadingStyle);
                    $activeSheetIndex += 1;
                }
                if(isset($options['lwdBDG'])){
                    $phpExcelObject->createSheet();
                    $phpExcelObject->setActiveSheetIndex($activeSheetIndex);
                    foreach($options['learnersBDG'] as $key => &$array){
                        array_unshift($array, $key);
                    }
                    $phpExcelObject->getActiveSheet()->fromArray(array(
                        'Male', 'Female'
                    ), null, 'B1', true);
                    $phpExcelObject->getActiveSheet()->fromArray($options['learnersBDG'], null, 'A2', true);
                    $phpExcelObject->getActiveSheet()->setTitle("Learners by Disability & Sex");
                    $maxCell = $phpExcelObject->getActiveSheet()->getHighestRowAndColumn();
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'. $maxCell['column'] . $maxCell['row'])->applyFromArray($styleArray);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'.$maxCell['column'].'1')->applyFromArray($columnHeadingStyle);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:A'.$maxCell['row'])->applyFromArray($columnHeadingStyle);
                    $activeSheetIndex += 1;
                }
                if(isset($options['snLearners'])){
                    $phpExcelObject->createSheet();
                    $phpExcelObject->setActiveSheetIndex($activeSheetIndex);
                    $phpExcelObject->getActiveSheet()->fromArray(
                        array('First Name(s)', 'Last Name', 'Present Address', 'Sex', 'Date of Birth', 'Distance to School',
                            'Guardian First Name(s)','Guardian Last Name', 'Guardian Sex', 'Guardian Occupation',
                            'Household Type'), null, 'A1', true
                    );
                    $phpExcelObject->getActiveSheet()->fromArray($options['snLearners'], null, 'A2', true);
                    $phpExcelObject->getActiveSheet()->setTitle("List of Learners");
                    $maxCell = $phpExcelObject->getActiveSheet()->getHighestRowAndColumn();
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'. $maxCell['column'] . $maxCell['row'])->applyFromArray($styleArray);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'.$maxCell['column'].'1')->applyFromArray($columnHeadingStyle);
                    $activeSheetIndex += 1;
                }
                if(isset($options['snTeachers'])){
                    $phpExcelObject->createSheet();
                    $phpExcelObject->setActiveSheetIndex($activeSheetIndex);
                    $phpExcelObject->getActiveSheet()->fromArray(
                        array('First Name(s)', 'Last Name', 'Emp. Number', 'Sex', 'Qualification', 'Speciality',
                            'Yr. Started Teaching',
                            'Teacher Type'), null, 'A1', true
                    );
                    $phpExcelObject->getActiveSheet()->fromArray($options['snTeachers'], null, 'A2', true);
                    $phpExcelObject->getActiveSheet()->setTitle("List of Learners");
                    $maxCell = $phpExcelObject->getActiveSheet()->getHighestRowAndColumn();
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'. $maxCell['column'] . $maxCell['row'])->applyFromArray($styleArray);
                    $phpExcelObject->getActiveSheet()
                        ->getStyle('A1:'.$maxCell['column'].'1')->applyFromArray($columnHeadingStyle);
                    $activeSheetIndex += 1;
                }

                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex(0);
                // create the writer
                $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                // adding headers
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'IED-'.$school['school_name'].'_custom-report-'.date('d-m-Y').'.xlsx'
                );
                $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'max-age=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);
                return $response;
                exit;
            }

        }

        return $this->render('school/reports/reports_custom.html.twig', array('form' => $form->createView()));
    }
}
?>