<?php
function getVoyagerReserves($cc,$section=''){
    $dsn='oci:dbname=/'.'';
    $db=new PDO($dsn,'','');
    if(!$db) return array('error'=>'db connect error');
    $cc=strtoupper(preg_replace('/\s+/','',$cc));
    if(!$section){
        $sql="
			SELECT DISTINCT
				RESERVE_LIST.RESERVE_LIST_ID
				,RESERVE_LIST.LIST_TITLE
			FROM
				RESERVE_LIST
				,RESERVE_LIST_COURSES
				,COURSE
			WHERE
				RESERVE_LIST.RESERVE_LIST_ID=RESERVE_LIST_COURSES.RESERVE_LIST_ID
				AND
				RESERVE_LIST_COURSES.COURSE_ID=COURSE.COURSE_ID
				AND
				COURSE.COURSE_NUMBER=:coursenumber
      ORDER BY
				RESERVE_LIST.LIST_TITLE
 				,RESERVE_LIST.RESERVE_LIST_ID
		";
        $bind=array(
            'coursenumber'=>$cc
        );
    }else{
        $section=trim(strtoupper($section));
        $sql="
			SELECT DISTINCT
				RESERVE_LIST.RESERVE_LIST_ID
				,RESERVE_LIST.LIST_TITLE
			FROM
				RESERVE_LIST
				,RESERVE_LIST_COURSES
				,COURSE
				,CLASS_SECTION
			WHERE
				RESERVE_LIST.RESERVE_LIST_ID=RESERVE_LIST_COURSES.RESERVE_LIST_ID
				AND
				RESERVE_LIST_COURSES.COURSE_ID=COURSE.COURSE_ID
				AND
				RESERVE_LIST_COURSES.SECTION_ID=CLASS_SECTION.SECTION_ID
				AND
				COURSE.COURSE_NUMBER=:coursenumber
				AND
				CLASS_SECTION.SECTION_NUMBER=:section
      ORDER BY
				RESERVE_LIST.LIST_TITLE
 				,RESERVE_LIST.RESERVE_LIST_ID
		";
        $bind=array(
            'coursenumber'=>$cc,
            'section'=>$section
        );
    }
    $stmt=$db->prepare($sql);
    $stmt->execute($bind);
    //return $stmt->errorInfo();
    if($stmt->rowCount()===0){
        //return array('searched'=>$cc);
    }
    $ret=array();
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        $r=array(
            'source'=>'VoyagerReserves',
            'id'=>$row['RESERVE_LIST_ID'],
            'title'=>$row['LIST_TITLE'],
            'html'=>'<br />'
        );
        $ret[]=$r;
    }
    return $ret;
}