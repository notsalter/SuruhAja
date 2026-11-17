<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../utils.php';

class UtilsTest extends TestCase {
    public function testValidateRating() {
        $this->assertTrue(validate_rating(3));
        $this->assertFalse(validate_rating(0));
        $this->assertFalse(validate_rating(6));
    }
}
?>