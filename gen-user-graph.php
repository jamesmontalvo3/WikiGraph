<?php
/*IF( p_to.page_is_redirect = 0, p_to.page_title, CONCAT( '* ', p_to.page_title, " --> ", (SELECT rd_title FROM redirect WHERE rd_from = p_to.page_id) ) ) AS to_title */

/*

for raw edges:
SET @start = 20150101000000;
SET @end   = 20150331000000;

SELECT DISTINCT
    wiretap.user_name,
    IF( page.page_is_redirect = 0, page.page_id, (
            SELECT redpage.page_id FROM redirect 
            LEFT JOIN page AS redpage ON redirect.rd_namespace = redpage.page_namespace AND redirect.rd_title = redpage.page_title
            WHERE rd_from = page.page_id
     ) ) AS page_id,
    IF( page.page_is_redirect = 0, page.page_title, (SELECT rd_title FROM redirect WHERE rd_from = page.page_id) ) AS page_title
FROM wiretap
RIGHT JOIN page ON
    wiretap.page_id = page.page_id
RIGHT JOIN user ON
    user.user_name = wiretap.user_name
LEFT JOIN user_groups ON
    user_groups.ug_user = user.user_id
WHERE
    wiretap.hit_timestamp > @start
    AND wiretap.hit_timestamp < @end
    AND wiretap.page_id IS NOT NULL
    AND wiretap.page_id != 0
    AND page.page_id != 1
    AND user_groups.ug_group = "CX3"
    AND page.page_namespace = 0;


NODE INFO

SELECT
    page_namespace,
    page_title,
    page_id,
    (
        SELECT
            GROUP_CONCAT( cl_to )
        FROM categorylinks
        WHERE
            cl_from = page_id 
            AND cl_to IN ("Meeting_Minutes","Lesson_Learned","OCAD","Tool","NCW","EVA","Inhibit","Crew","ORU","Person","EMU_Component","Generic_Hardware","EVA_Development_Process_Work_Instruction")   
    ) AS categories 
FROM page
WHERE
    page_namespace = 0
    AND page_is_redirect = 0

*/

function createNodeName ( $ns, $title ) {
    return trim( $ns ) . ':' . trim( $title );
}

function addNode ( $nodeName, $categories ) {
    global $nodeKey, $gexf;

    if ( ! array_key_exists( $nodeName, $nodeKey ) ) {
        $node = new GexfNode( $nodeName );
        $nodeKey[ $nodeName ] = $node->getNodeId();
        
        if ( count( $categories ) == 0 ) {
            $categories = array( "uncategorized" );
        }
        foreach( $categories as $cat ) {
            $node->addNodeAttribute( "category", $cat );
        }

        $gexf->addNode( $node );
    }

    return $nodeKey[ $nodeName ];

}

require_once __DIR__ . '/GEXF-library/Gexf.class.php';

// create new graph
$gexf = new Gexf();
$gexf->setTitle( "MediaWiki Graph" );
$gexf->setEdgeType( GEXF_EDGE_UNDIRECTED );
$gexf->setCreator( "My Wiki" );
$frequency = 1;

/*
$rawNodes = file_get_contents( __DIR__ . '/Data-Source/rawNodes.txt' );
$rawNodes = explode( "\n", $rawNodes );

$nodeKey = array();
foreach( $rawNodes as $rawNodeLine ) {

    $nodeData = explode( "\t", $rawNodeLine );

    $nodeName = createNodeName ( $nodeData[0], $nodeData[1] );

    // blacklist some pages
    $blacklistedPages = array(
        "EVA_Mission_History",
        "List_of_EVA_Task_OCADs",
        "Mission_History",
        "EVA_History",
        "Hot_Water_12_November_2013_(Development_Page)",
        "List_of_Generic_Notes_Cautions_and_Warnings",
        "2013_Loop_A_Pump_Module_Troubleshooting/OCADs",
        "Lessons_Learned",
        "Articles_to_be_expanded",
        "Articles_that_can_be_featured",
        "List_of_ISS_Increments",
        "Image_Gallery_of_EVA_Tools",
        "ISS_EVA_SMALL_TOOLS",
        "Subject_Matter_Expert",
        "Visiting_Vehicles",
        "EVA_Tools",
        "Meeting_Minutes",
        "List_of_Generic_EVA_Inhibits",
        "Generic_EVA_Inhibit_Pad",
        "Scheduled_EVAs",
        "ISS_EVA_MAINT_1",
        "FLIGHT",
        "MCC_Voice_Loops",
        "List_of_EVAs_by_PET",
        "Articles_with_unsourced_statements"
    );
    if ( in_array( trim( $nodeData[1] ), $blacklistedPages ) ) {
        continue;
    }

    $categories = explode( ',', $nodeData[2] );
    $categories = array_map( function( $e ) { return trim( $e ); } , $categories );

    // skip certain categories
    if ( in_array( "Person", $categories ) 
        || in_array( "Crew", $categories ) 
        || in_array( "Meeting_Minutes", $categories ) 
        ) {
        continue;
    }

    $node = new GexfNode( $nodeName );

    foreach( $categories as $cat ) {
        $node->addNodeAttribute( "category", $cat );
    }

    $nodeKey[ $nodeName ] = $node->getNodeId();

    $gexf->addNode( $node );

}
unset( $rawNodes );
*/

$rawNodes = file_get_contents( __DIR__ . '/Data-Source/rawNodes2.txt' );
$rawNodes = explode( "\n", $rawNodes );

$nodeCats = array();
foreach( $rawNodes as $nodeData ) {

    $nodeData = explode( "\t", $nodeData );
    $nodeData = array_map( function( $e ) { return trim( $e ); }, $nodeData );

    //list( $ns, $title, $pageId, $cats ) 

    $nodeCats[ $nodeData[2] ] = explode( ',', $nodeData[3] );
}

// print_r( $nodeCats );die();


$rawEdges = file_get_contents( __DIR__ . '/Data-Source/UserPageHits2.txt' );
$rawEdges = explode( "\n", $rawEdges );

$nodeKey = array();

$edges = array();
$count = 0;
foreach( $rawEdges as $rawEdgeLine ) {

    //$edgeData = explode( "\t", $rawEdgeLine );

    list( $username, $pageId, $pageName ) = array_map( function( $e ) { return trim($e); }, explode( "\t", $rawEdgeLine ) );

    // $fromNodeName = trim( $edgeData[0] );
    // $toNodeName = trim( $edgeData[2] );
    $fromNodeName = trim( $username );
    $toNodeName = trim( $pageName );

    // user node
    $fromNodeId = addNode( $fromNodeName, array( "User" ) );
    
    if ( array_key_exists( $pageId, $nodeCats ) ) {
        $pageCats = $nodeCats[ $pageId ];
    }
    else {
        $pageCats = array();
    }

    // page node
    $toNodeId   = addNode( $toNodeName, $pageCats );

    $edge_id = $gexf->addEdge(
        $gexf->nodeObjects[ $fromNodeId ],
        $gexf->nodeObjects[ $toNodeId ],
        $frequency
    );
    $count++;

}
unset( $rawEdges );
echo "\n$count edges created\n";

/*

// fill bi-partite graph
foreach ($userHashtags as $user => $hashtags) {
    foreach ($hashtags as $hashtag => $frequency) {

        // make node 1
        $node1 = new GexfNode($user);
        $node1->addNodeAttribute("type", 'user', $type = "string");
        $gexf->addNode($node1);

        // make node 2
        $node2 = new GexfNode($hashtag);
        $node2->addNodeAttribute("type", 'hashtag', $type = "string");
        $gexf->addNode($node2);

        // create edge  
        $edge_id = $gexf->addEdge($node1, $node2, $frequency);
    }
}
*/


// render the file
$gexf->render();

$timestamp = date( "Ymd_his" );

// write out the file
file_put_contents( __DIR__ . "/Data-Output/output-$timestamp.gexf", $gexf->gexfFile );