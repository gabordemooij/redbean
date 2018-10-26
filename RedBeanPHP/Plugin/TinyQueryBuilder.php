<?php

/**
 * Tiny Query Builder
 * 
 * $sql = build_query([
 * [               'SELECT * FROM book'],
 * [$title         ,'WHERE','title = ?'],
 * [$price         ,'AND','price < ?'],
 * [$order         ,'ORDER BY ? ASC'],
 * [$limit         ,'LIMIT ?']
 * ]);
 *
 * Now, if we have a $title and a $price the query will be:
 * 'SELECT * FROM book WHERE title = ? AND price < ? '
 * If we only have a $price and a $limit:
 * 'SELECT * FROM book WHERE price < ? LIMIT ?'
 * The Query Builder works very easy, it simply loops through the array,
 * each element is another array inside this main array,
 * let's call this inner array a 'piece'.
 * A piece can have one, two or three elements.
 * If it has one element, the element is simply concatenated to the final query.
 * If a piece has two elements, the second element will be 
 * concatenated only if the first evaluates to TRUE.
 * Finally a piece having three elements works the same as a piece with two elements,
 * except that it will use the glue provided in the second element
 * to concat the value of the third element. The glue acts as a little tube of glue.
 * If there is still glue left in the tube (WHERE) it will preserve this
 * until it can be applied (so the first AND will be ignored in case of a WHERE condition). 
 */
R::ext('buildQuery', function($pieces) {
  $sql = '';
  $glue = NULL;
  foreach( $pieces as $piece ) {
    $n = count( $piece );  
    switch( $n ) {
      case 1:
        $sql .= " {$piece[0]} ";
        break;
      case 2:
        $glue = NULL;
        if (!is_null($piece[0])) $sql .= " {$piece[1]} ";
        break;
      case 3:
        $glue = ( is_null( $glue ) ) ? $piece[1] : $glue;
	if (!is_null($piece[0])) { 
		$sql .= " {$glue} {$piece[2]} ";
		$glue = NULL;
	}
        break;
    }
  }
  return $sql;
});
