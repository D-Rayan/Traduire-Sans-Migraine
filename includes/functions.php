<?php

function displayBigNumber($number) {
    // Display a big number with a ' every 3 digits
    return number_format($number, 0, ",", "'");
}