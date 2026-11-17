<?php
function validate_rating(int $rating): bool {
    return $rating >= 1 && $rating <= 5;
}
?>