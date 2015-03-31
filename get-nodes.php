<?php


/*

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


*/