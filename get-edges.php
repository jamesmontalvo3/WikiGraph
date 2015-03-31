<?php


/*



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
ORDER BY to_title



// this was in there, but has been removed for some reason
IF( p_to.page_is_redirect = 0, p_to.page_title, CONCAT( '* ', p_to.page_title, " --> ", (SELECT rd_title FROM redirect WHERE rd_from = p_to.page_id) ) ) AS to_title

*/