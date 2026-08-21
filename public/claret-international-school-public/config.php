<?php
/**
 * Claret International School — site configuration
 * Central place for content that repeats across pages/partials.
 * Swap this for DB-driven data later without touching the templates.
 */

// ─── SEO / deployment ────────────────────────────────────────────────────────
$site_base_url   = 'https://claretschools.xo.je';   // no trailing slash
$og_image_default = $site_base_url . '/assets/img/hero-carousel.jpg';
// ─────────────────────────────────────────────────────────────────────────────

$school = [
    'name'        => 'Claret International School',
    'short_name'  => 'Claret',
    'tagline'     => 'Discipline, Integrity & Ardour',
    'phone'       => '08037881737',
    'phone_href'  => '+2348037881737',
    'address_line1' => 'Plot 700, Gitto Street, Mabushi',
    'address_line2' => 'Mabushi, Abuja, Federal Capital Territory, 900104, Nigeria',
    'lat'         => '9.079895184838914',
    'lng'         => '7.438253934813874',
    'director'    => [
        'name'  => 'Theresa Titilayo',
        'role'  => 'School Director',
        'quote' => 'Claret international school Mabushi Abuja, is a unique citadel of learning raising 21st century leaders for tomorrow\'s work place.',
    ],
    'about_long' => 'Claret International School, located at Plot 700, Gitto Road, Mabushi, Abuja FCT Nigeria, was established to provide Private Nursery, Primary and Secondary School Education. The School is a 21st century citadel of learning, raising 21st century leaders. Our objective is to ensure that our learners are well taught by qualified tutors, in a well-equipped and safe environment.',
    'classification' => [
        'School Curriculum'       => 'Nigerian National Curriculum, British Curriculum, Montessori Curriculum',
        'School Classification'   => 'Private School',
        'Gender Composition'      => 'Co-Educational School',
        'Student Residence'       => 'Day School',
        'School Bus Availability' => 'Available',
    ],
    'curricula' => [
        'Nigerian National Curriculum',
        'British Curriculum',
        'Montessori Curriculum',
    ],
    'clubs' => [
        'Academic Clubs' => ['Science Club', 'Math Club', 'Debate Club'],
        'Technology & STEM Clubs' => ['Coding Club'],
        'Arts & Crafts Clubs' => ['Art Club', 'Drama Club', 'Art and Craft Club'],
        'Sports & Fitness Clubs' => ['Martial Arts Club', 'Outdoor Adventure Club', 'Fitness and Wellness Club'],
        'Enrichment' => ['Dance Club'],
    ],
    'stats' => [
        ['value' => '10+', 'label' => 'Years of excellence'],
        ['value' => '98%', 'label' => 'Pass rate'],
        ['value' => '1:12', 'label' => 'Teacher to learner ratio'],
    ],
];

$nav = [
    '/'           => 'Home',
    '/about'      => 'About us',
    '/admissions' => 'Admissions',
    '/contact'    => 'Contact',
];

$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$current_page = $current_page === 'index' ? '/' : '/' . $current_page;
