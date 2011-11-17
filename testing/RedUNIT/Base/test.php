<?php






testpack("unrelated");

testpack("Test parameter binding");




//this module tests whether values we store are the same we get returned
testpack("setting and getting values, pdo/types");



testpack("test optimization related() ");
R::$writer->setBeanFormatter( new TestFormatter );
$book = R::dispense("book");
$book->title = "ABC";
$page = R::dispense("page");
$page->content = "lorem ipsum 123 ... ";
R::associate($book,$page);
asrt(count(R::related($book,"page"," content LIKE '%123%' ") ),1);





printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");


