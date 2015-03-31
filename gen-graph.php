<?php
/*IF( p_to.page_is_redirect = 0, p_to.page_title, CONCAT( '* ', p_to.page_title, " --> ", (SELECT rd_title FROM redirect WHERE rd_from = p_to.page_id) ) ) AS to_title */

/*

for raw edges:

SELECT DISTINCT
    p_from.page_namespace AS from_namespace,
    p_from.page_title AS from_title,
    IF( p_to.page_is_redirect = 0, p_to.page_namespace, (SELECT rd_namespace FROM redirect WHERE rd_from = p_to.page_id) ) AS to_namespace,
    IF( p_to.page_is_redirect = 0, p_to.page_title, (SELECT rd_title FROM redirect WHERE rd_from = p_to.page_id) ) AS to_title
FROM pagelinks AS pl
LEFT JOIN page AS p_from ON (p_from.page_id = pl.pl_from)
LEFT JOIN page AS p_to ON (p_to.page_namespace = pl.pl_namespace AND p_to.page_title = pl.pl_title)
WHERE
    p_from.page_namespace = 0 
    AND p_to.page_namespace = 0
    AND p_from.page_is_redirect = 0




for raw nodes:

SELECT
    page_namespace,
    page_title,
    (
        SELECT
            GROUP_CONCAT( cl_to )
        FROM categorylinks
        WHERE cl_from = page_id     
    ) AS categories 
FROM page
WHERE
    page_namespace = 0
    AND page_is_redirect = 0


SELECT
    page_namespace,
    page_title
FROM page
WHERE
    page_namespace = 0
    AND page_is_redirect = 0

*/

require_once __DIR__ . '/GEXF-library/Gexf.class.php';

// create new graph
$gexf = new Gexf();
$gexf->setTitle( "MediaWiki Graph" );
$gexf->setEdgeType( GEXF_EDGE_DIRECTED );
$gexf->setCreator( "My Wiki" );
$frequency = 1;


$rawNodes = file_get_contents( __DIR__ . '/rawNodes.txt' );
$rawNodes = explode( "\n", $rawNodes );

$nodeKey = array();
foreach( $rawNodes as $rawNodeLine ) {

    $nodeData = explode( "\t", $rawNodeLine );

    $nodeName = $nodeData[0] . ':' . $nodeData[1];

    // blacklist some pages
    if ( in_array( $nodeData[1], array("EVA_Mission_History", "List_of_EVA_Task_OCADs", "Mission_History", "EVA_History", "Hot_Water_12_November_2013_(Development_Page)", "List_of_Generic_Notes_Cautions_and_Warnings", "2013_Loop_A_Pump_Module_Troubleshooting/OCADs", "Lessons_Learned", "Articles_to_be_expanded", "Articles_that_can_be_featured", "List_of_ISS_Increments", "Image_Gallery_of_EVA_Tools", "ISS_EVA_SMALL_TOOLS", "Subject_Matter_Expert", "Visiting_Vehicles", "EVA_Tools", "Meeting_Minutes", "List_of_Generic_EVA_Inhibits", "Generic_EVA_Inhibit_Pad", "Scheduled_EVAs", "ISS_EVA_MAINT_1", "FLIGHT", "MCC_Voice_Loops", "List_of_EVAs_by_PET", "Articles_with_unsourced_statements") ) ) {
        continue;
    }

    $categories = explode( ',', $nodeData[2] );

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


$rawEdges = file_get_contents( __DIR__ . '/rawEdges.txt' );
$rawEdges = explode( "\n", $rawEdges );

$edges = array();
foreach( $rawEdges as $rawEdgeLine ) {

    $edgeData = explode( "\t", $rawEdgeLine );

    if ( ! array_key_exists( $edgeData[2] . ':' . $edgeData[3], $nodeKey ) 
         || ! array_key_exists( $edgeData[0] . ':' . $edgeData[1], $nodeKey ) 
        ) {
        continue;
    } 



    $fromNodeId = $nodeKey[ $edgeData[0] . ':' . $edgeData[1] ];


    $toNodeId   = $nodeKey[ $edgeData[2] . ':' . $edgeData[3] ];

    if ( array_key_exists( $fromNodeId, $gexf->nodeObjects ) && array_key_exists( $toNodeId, $gexf->nodeObjects ) ) {

        $edge_id = $gexf->addEdge(
            $gexf->nodeObjects[ $fromNodeId ],
            $gexf->nodeObjects[ $toNodeId ],
            $frequency
        );

    }
}
unset( $rawEdges );


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

// write out the file
file_put_contents( __DIR__ . '/file.gexf', $gexf->gexfFile );