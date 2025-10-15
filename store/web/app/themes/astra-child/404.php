<?php

/**
 * The template for displaying 404 pages (not found).
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Astra
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

get_header(); ?>


<div id="primary" <?php astra_primary_class(); ?>>

    <div class="m-auto max-w-sm text-center flex flex-col mt-[30vh]" style="margin-top:30vh">
        <h1 class="text-app-hading font-semibold text-3xl" style="font-size: 30px">404</h1>
        <p class="text-app-text text-base">The requested page was not found.
            Try going back to the previous page or see our <a class="text-app-primary">Help Center</a> for more information.
        </p>
    </div>

</div><!-- #primary -->