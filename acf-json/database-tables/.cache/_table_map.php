<?php defined( 'WPINC' ) or die('Nothing to see here.'); return array (
  'tables' => 
  array (
    'bridge_library_course_meta' => 
    array (
      'name' => 'bridge_library_course_meta',
      'relationship' => 
      array (
        'type' => 'post',
        'post_type' => 'course',
      ),
      'primary_key' => 
      array (
        0 => 'id',
      ),
      'keys' => 
      array (
        0 => 
        array (
          'name' => 'post_id',
          'columns' => 
          array (
            0 => 'post_id',
          ),
          'unique' => true,
        ),
      ),
      'columns' => 
      array (
        0 => 
        array (
          'name' => 'id',
          'format' => '%d',
          'type' => 'bigint(20)',
          'null' => false,
          'auto_increment' => true,
          'unsigned' => true,
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'id',
          ),
        ),
        1 => 
        array (
          'name' => 'post_id',
          'format' => '%d',
          'type' => 'bigint(20)',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'post_id',
          ),
        ),
        2 => 
        array (
          'name' => 'alma_id',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alma_id',
            'key' => 'field_5cc2161e7b584',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'course_code',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_code',
            'key' => 'field_5cc05b642ba7f',
          ),
          'format' => '%s',
        ),
        4 => 
        array (
          'name' => 'academic_department_code',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_department_code',
            'key' => 'field_5dea86ee08933',
          ),
          'format' => '%s',
        ),
        5 => 
        array (
          'name' => 'course_number',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_number',
            'key' => 'field_5cc05bd1a9800',
          ),
          'format' => '%s',
        ),
        6 => 
        array (
          'name' => 'course_section',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_section',
            'key' => 'field_5cc05bdda9801',
          ),
          'format' => '%s',
        ),
        7 => 
        array (
          'name' => 'start_date',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'start_date',
            'key' => 'field_5cc05b722ba80',
          ),
          'format' => '%s',
        ),
        8 => 
        array (
          'name' => 'end_date',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'end_date',
            'key' => 'field_5cc05b802ba81',
          ),
          'format' => '%s',
        ),
        9 => 
        array (
          'name' => 'institution',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'institution',
            'key' => 'field_5cc201c1a852c',
          ),
          'format' => '%s',
        ),
        10 => 
        array (
          'name' => 'academic_department',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_department',
            'key' => 'field_5cc201daa852d',
          ),
          'format' => '%s',
        ),
        11 => 
        array (
          'name' => 'degree_level',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'degree_level',
            'key' => 'field_5cc201f1a852e',
          ),
          'format' => '%s',
        ),
        12 => 
        array (
          'name' => 'course_term',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_term',
            'key' => 'field_5cc201f9a852f',
          ),
          'format' => '%s',
        ),
        13 => 
        array (
          'name' => 'core_resources',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'core_resources',
            'key' => 'field_5d5d649bd2029',
          ),
          'format' => '%s',
        ),
        14 => 
        array (
          'name' => 'related_courses_resources',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'related_courses_resources',
            'key' => 'field_5cc326f90696b',
          ),
          'format' => '%s',
        ),
        15 => 
        array (
          'name' => 'librarians',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarians',
            'key' => 'field_5e5819970fbfd',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => '626a9b61200d7628c9e5dc33d482fbae',
      'modified' => 1670511939,
      'type' => 'meta',
    ),
    'bridge_library_user_meta' => 
    array (
      'name' => 'bridge_library_user_meta',
      'relationship' => 
      array (
        'type' => 'user',
      ),
      'primary_key' => 
      array (
        0 => 'id',
      ),
      'keys' => 
      array (
        0 => 
        array (
          'name' => 'user_id',
          'columns' => 
          array (
            0 => 'user_id',
          ),
          'unique' => true,
        ),
      ),
      'columns' => 
      array (
        0 => 
        array (
          'name' => 'id',
          'format' => '%d',
          'type' => 'bigint(20)',
          'null' => false,
          'auto_increment' => true,
          'unsigned' => true,
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'id',
          ),
        ),
        1 => 
        array (
          'name' => 'user_id',
          'format' => '%d',
          'type' => 'bigint(20)',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'user_id',
          ),
        ),
        2 => 
        array (
          'name' => 'alma_id',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alma_id',
            'key' => 'field_5cc9bff4b440d',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'alternate_id',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alternate_id',
            'key' => 'field_5d1f5dfee5704',
          ),
          'format' => '%s',
        ),
        4 => 
        array (
          'name' => 'primo_id',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_id',
            'key' => 'field_5cc9c036b440f',
          ),
          'format' => '%s',
        ),
        5 => 
        array (
          'name' => 'google_id',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'google_id',
            'key' => 'field_5cc9c025b440e',
          ),
          'format' => '%s',
        ),
        6 => 
        array (
          'name' => 'picture_url',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'picture_url',
            'key' => 'field_5cc9c079b4410',
          ),
          'format' => '%s',
        ),
        7 => 
        array (
          'name' => 'expiration_date',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'expiration_date',
            'key' => 'field_5cf696493f092',
          ),
          'format' => '%s',
        ),
        8 => 
        array (
          'name' => 'bridge_library_institution',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'bridge_library_institution',
            'key' => 'field_5ccc5a589c115',
          ),
          'format' => '%s',
        ),
        9 => 
        array (
          'name' => 'courses',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'courses',
            'key' => 'field_5cc9c0c5b4412',
          ),
          'format' => '%s',
        ),
        10 => 
        array (
          'name' => 'academic_departments',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_departments',
            'key' => 'field_5cc9c1abb4413',
          ),
          'format' => '%s',
        ),
        11 => 
        array (
          'name' => 'librarians',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarians',
            'key' => 'field_5cfeb4860abf8',
          ),
          'format' => '%s',
        ),
        12 => 
        array (
          'name' => 'circulation_data',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'circulation_data',
            'key' => 'field_5d52eb9a29516',
          ),
          'format' => '%s',
        ),
        13 => 
        array (
          'name' => 'resources',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resources',
            'key' => 'field_5cca170b17493',
          ),
          'format' => '%s',
        ),
        14 => 
        array (
          'name' => 'user_favorites',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'user_favorites',
            'key' => 'field_5d32106324c02',
          ),
          'format' => '%s',
        ),
        15 => 
        array (
          'name' => 'primo_favorites',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_favorites',
            'key' => 'field_5ccc9d5d6d7c7',
          ),
          'format' => '%s',
        ),
        16 => 
        array (
          'name' => 'courses_cache_updated',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'courses_cache_updated',
            'key' => 'field_5cdaf2d7d64af',
          ),
          'format' => '%s',
        ),
        17 => 
        array (
          'name' => 'resources_cache_updated',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resources_cache_updated',
            'key' => 'field_5cdaf313d64b0',
          ),
          'format' => '%s',
        ),
        18 => 
        array (
          'name' => 'primo_favorites_cache_updated',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_favorites_cache_updated',
            'key' => 'field_5cdaf323d64b1',
          ),
          'format' => '%s',
        ),
        19 => 
        array (
          'name' => 'librarians_cache_updated',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarians_cache_updated',
            'key' => 'field_5cfeb4a50abf9',
          ),
          'format' => '%s',
        ),
        20 => 
        array (
          'name' => 'circulation_data_cache_updated',
          'type' => 'longtext',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'circulation_data_cache_updated',
            'key' => 'field_5d52eca029517',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => 'da294d65eae0b0d48980a025580155a2',
      'modified' => 1690319799,
      'type' => 'meta',
    ),
    'bridge_library_librarian_meta' => 
    array (
      'name' => 'bridge_library_librarian_meta',
      'relationship' => 
      array (
        'type' => 'post',
        'post_type' => 'librarian',
      ),
      'primary_key' => 
      array (
        0 => 'id',
      ),
      'keys' => 
      array (
        0 => 
        array (
          'name' => 'post_id',
          'columns' => 
          array (
            0 => 'post_id',
          ),
          'unique' => true,
        ),
      ),
      'columns' => 
      array (
        0 => 
        array (
          'name' => 'id',
          'format' => '%d',
          'null' => false,
          'auto_increment' => true,
          'unsigned' => true,
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'id',
          ),
        ),
        1 => 
        array (
          'name' => 'post_id',
          'format' => '%d',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'post_id',
          ),
        ),
        2 => 
        array (
          'name' => 'librarian_user_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarian_user_id',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'academic_department',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_department',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => '387c5d840a7c060e7eb6c25535239a0c',
      'modified' => 1576860741,
      'type' => 'meta',
    ),
    'bridge_library_resource_meta' => 
    array (
      'name' => 'bridge_library_resource_meta',
      'relationship' => 
      array (
        'type' => 'post',
        'post_type' => 'resource',
      ),
      'primary_key' => 
      array (
        0 => 'id',
      ),
      'keys' => 
      array (
        0 => 
        array (
          'name' => 'post_id',
          'columns' => 
          array (
            0 => 'post_id',
          ),
          'unique' => true,
        ),
      ),
      'columns' => 
      array (
        0 => 
        array (
          'name' => 'id',
          'format' => '%d',
          'null' => false,
          'auto_increment' => true,
          'unsigned' => true,
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'id',
          ),
        ),
        1 => 
        array (
          'name' => 'post_id',
          'format' => '%d',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'post_id',
          ),
        ),
        2 => 
        array (
          'name' => 'related_courses_resources',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'related_courses_resources',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'related_departments',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'related_departments',
          ),
          'format' => '%s',
        ),
        4 => 
        array (
          'name' => 'institution',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'institution',
          ),
          'format' => '%s',
        ),
        5 => 
        array (
          'name' => 'url',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'url',
          ),
          'format' => '%s',
        ),
        6 => 
        array (
          'name' => 'image_url',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'image_url',
          ),
          'format' => '%s',
        ),
        7 => 
        array (
          'name' => 'primo_image_url',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_image_url',
          ),
          'format' => '%s',
        ),
        8 => 
        array (
          'name' => 'primo_image_info',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_image_info',
          ),
          'format' => '%s',
        ),
        9 => 
        array (
          'name' => 'alma_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alma_id',
          ),
          'format' => '%s',
        ),
        10 => 
        array (
          'name' => 'primo_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_id',
          ),
          'format' => '%s',
        ),
        11 => 
        array (
          'name' => 'libguides_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'libguides_id',
          ),
          'format' => '%s',
        ),
        12 => 
        array (
          'name' => 'author',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'author',
          ),
          'format' => '%s',
        ),
        13 => 
        array (
          'name' => 'isbn',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'isbn',
          ),
          'format' => '%s',
        ),
        14 => 
        array (
          'name' => 'publication_year',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'publication_year',
          ),
          'format' => '%s',
        ),
        15 => 
        array (
          'name' => 'description',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'description',
          ),
          'format' => '%s',
        ),
        16 => 
        array (
          'name' => 'resource_format',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resource_format',
          ),
          'format' => '%s',
        ),
        17 => 
        array (
          'name' => 'resource_type',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resource_type',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => 'f7120b57fa931e17384a220de5416694',
      'modified' => 1619493655,
      'type' => 'meta',
    ),
  ),
  'table_names' => 
  array (
    0 => 'bridge_library_course_meta',
    1 => 'bridge_library_user_meta',
    2 => 'bridge_library_librarian_meta',
    3 => 'bridge_library_resource_meta',
  ),
  'types' => 
  array (
    'post' => 
    array (
      0 => 0,
      1 => 2,
      2 => 3,
    ),
    'user' => 
    array (
      0 => 1,
    ),
  ),
  'post_types' => 
  array (
    'course' => 
    array (
      0 => 0,
    ),
    'librarian' => 
    array (
      0 => 2,
    ),
    'resource' => 
    array (
      0 => 3,
    ),
  ),
  'join_tables' => 
  array (
  ),
  'sub_tables' => 
  array (
  ),
  'meta_tables' => 
  array (
  ),
  'acf_field_names' => 
  array (
    'post:course' => 
    array (
      'id' => 
      array (
        0 => 0,
      ),
      'post_id' => 
      array (
        0 => 0,
      ),
      'alma_id' => 
      array (
        0 => 0,
      ),
      'course_code' => 
      array (
        0 => 0,
      ),
      'academic_department_code' => 
      array (
        0 => 0,
      ),
      'course_number' => 
      array (
        0 => 0,
      ),
      'course_section' => 
      array (
        0 => 0,
      ),
      'start_date' => 
      array (
        0 => 0,
      ),
      'end_date' => 
      array (
        0 => 0,
      ),
      'institution' => 
      array (
        0 => 0,
      ),
      'academic_department' => 
      array (
        0 => 0,
      ),
      'degree_level' => 
      array (
        0 => 0,
      ),
      'course_term' => 
      array (
        0 => 0,
      ),
      'core_resources' => 
      array (
        0 => 0,
      ),
      'related_courses_resources' => 
      array (
        0 => 0,
      ),
      'librarians' => 
      array (
        0 => 0,
      ),
    ),
    'user' => 
    array (
      'id' => 
      array (
        0 => 1,
      ),
      'user_id' => 
      array (
        0 => 1,
      ),
      'alma_id' => 
      array (
        0 => 1,
      ),
      'alternate_id' => 
      array (
        0 => 1,
      ),
      'primo_id' => 
      array (
        0 => 1,
      ),
      'google_id' => 
      array (
        0 => 1,
      ),
      'picture_url' => 
      array (
        0 => 1,
      ),
      'expiration_date' => 
      array (
        0 => 1,
      ),
      'bridge_library_institution' => 
      array (
        0 => 1,
      ),
      'courses' => 
      array (
        0 => 1,
      ),
      'academic_departments' => 
      array (
        0 => 1,
      ),
      'librarians' => 
      array (
        0 => 1,
      ),
      'circulation_data' => 
      array (
        0 => 1,
      ),
      'resources' => 
      array (
        0 => 1,
      ),
      'user_favorites' => 
      array (
        0 => 1,
      ),
      'primo_favorites' => 
      array (
        0 => 1,
      ),
      'courses_cache_updated' => 
      array (
        0 => 1,
      ),
      'resources_cache_updated' => 
      array (
        0 => 1,
      ),
      'primo_favorites_cache_updated' => 
      array (
        0 => 1,
      ),
      'librarians_cache_updated' => 
      array (
        0 => 1,
      ),
      'circulation_data_cache_updated' => 
      array (
        0 => 1,
      ),
    ),
    'post:librarian' => 
    array (
      'id' => 
      array (
        0 => 2,
      ),
      'post_id' => 
      array (
        0 => 2,
      ),
      'librarian_user_id' => 
      array (
        0 => 2,
      ),
      'academic_department' => 
      array (
        0 => 2,
      ),
    ),
    'post:resource' => 
    array (
      'id' => 
      array (
        0 => 3,
      ),
      'post_id' => 
      array (
        0 => 3,
      ),
      'related_courses_resources' => 
      array (
        0 => 3,
      ),
      'related_departments' => 
      array (
        0 => 3,
      ),
      'institution' => 
      array (
        0 => 3,
      ),
      'url' => 
      array (
        0 => 3,
      ),
      'image_url' => 
      array (
        0 => 3,
      ),
      'primo_image_url' => 
      array (
        0 => 3,
      ),
      'primo_image_info' => 
      array (
        0 => 3,
      ),
      'alma_id' => 
      array (
        0 => 3,
      ),
      'primo_id' => 
      array (
        0 => 3,
      ),
      'libguides_id' => 
      array (
        0 => 3,
      ),
      'author' => 
      array (
        0 => 3,
      ),
      'isbn' => 
      array (
        0 => 3,
      ),
      'publication_year' => 
      array (
        0 => 3,
      ),
      'description' => 
      array (
        0 => 3,
      ),
      'resource_format' => 
      array (
        0 => 3,
      ),
      'resource_type' => 
      array (
        0 => 3,
      ),
    ),
  ),
  'acf_field_keys' => 
  array (
    'post:course' => 
    array (
      'field_5cc2161e7b584' => 'alma_id',
      'field_5cc05b642ba7f' => 'course_code',
      'field_5dea86ee08933' => 'academic_department_code',
      'field_5cc05bd1a9800' => 'course_number',
      'field_5cc05bdda9801' => 'course_section',
      'field_5cc05b722ba80' => 'start_date',
      'field_5cc05b802ba81' => 'end_date',
      'field_5cc201c1a852c' => 'institution',
      'field_5cc201daa852d' => 'academic_department',
      'field_5cc201f1a852e' => 'degree_level',
      'field_5cc201f9a852f' => 'course_term',
      'field_5d5d649bd2029' => 'core_resources',
      'field_5cc326f90696b' => 'related_courses_resources',
      'field_5e5819970fbfd' => 'librarians',
    ),
    'user' => 
    array (
      'field_5cc9bff4b440d' => 'alma_id',
      'field_5d1f5dfee5704' => 'alternate_id',
      'field_5cc9c036b440f' => 'primo_id',
      'field_5cc9c025b440e' => 'google_id',
      'field_5cc9c079b4410' => 'picture_url',
      'field_5cf696493f092' => 'expiration_date',
      'field_5ccc5a589c115' => 'bridge_library_institution',
      'field_5cc9c0c5b4412' => 'courses',
      'field_5cc9c1abb4413' => 'academic_departments',
      'field_5cfeb4860abf8' => 'librarians',
      'field_5d52eb9a29516' => 'circulation_data',
      'field_5cca170b17493' => 'resources',
      'field_5d32106324c02' => 'user_favorites',
      'field_5ccc9d5d6d7c7' => 'primo_favorites',
      'field_5cdaf2d7d64af' => 'courses_cache_updated',
      'field_5cdaf313d64b0' => 'resources_cache_updated',
      'field_5cdaf323d64b1' => 'primo_favorites_cache_updated',
      'field_5cfeb4a50abf9' => 'librarians_cache_updated',
      'field_5d52eca029517' => 'circulation_data_cache_updated',
    ),
  ),
  'acf_field_key_name_patterns' => 
  array (
  ),
  'acf_sub_table_owners' => 
  array (
  ),
  'acf_field_name_patterns' => 
  array (
  ),
  'acf_field_column_types' => 
  array (
    'bridge_library_course_meta' => 
    array (
      'id' => 'bigint(20)',
      'post_id' => 'bigint(20)',
      'alma_id' => 'longtext',
      'course_code' => 'longtext',
      'academic_department_code' => 'longtext',
      'course_number' => 'longtext',
      'course_section' => 'longtext',
      'start_date' => 'longtext',
      'end_date' => 'longtext',
      'institution' => 'longtext',
      'academic_department' => 'longtext',
      'degree_level' => 'longtext',
      'course_term' => 'longtext',
      'core_resources' => 'longtext',
      'related_courses_resources' => 'longtext',
      'librarians' => 'longtext',
    ),
    'bridge_library_user_meta' => 
    array (
      'id' => 'bigint(20)',
      'user_id' => 'bigint(20)',
      'alma_id' => 'longtext',
      'alternate_id' => 'longtext',
      'primo_id' => 'longtext',
      'google_id' => 'longtext',
      'picture_url' => 'longtext',
      'expiration_date' => 'longtext',
      'bridge_library_institution' => 'longtext',
      'courses' => 'longtext',
      'academic_departments' => 'longtext',
      'librarians' => 'longtext',
      'circulation_data' => 'longtext',
      'resources' => 'longtext',
      'user_favorites' => 'longtext',
      'primo_favorites' => 'longtext',
      'courses_cache_updated' => 'longtext',
      'resources_cache_updated' => 'longtext',
      'primo_favorites_cache_updated' => 'longtext',
      'librarians_cache_updated' => 'longtext',
      'circulation_data_cache_updated' => 'longtext',
    ),
    'bridge_library_librarian_meta' => 
    array (
      'id' => 'longtext',
      'post_id' => 'longtext',
      'librarian_user_id' => 'longtext',
      'academic_department' => 'longtext',
    ),
    'bridge_library_resource_meta' => 
    array (
      'id' => 'longtext',
      'post_id' => 'longtext',
      'related_courses_resources' => 'longtext',
      'related_departments' => 'longtext',
      'institution' => 'longtext',
      'url' => 'longtext',
      'image_url' => 'longtext',
      'primo_image_url' => 'longtext',
      'primo_image_info' => 'longtext',
      'alma_id' => 'longtext',
      'primo_id' => 'longtext',
      'libguides_id' => 'longtext',
      'author' => 'longtext',
      'isbn' => 'longtext',
      'publication_year' => 'longtext',
      'description' => 'longtext',
      'resource_format' => 'longtext',
      'resource_type' => 'longtext',
    ),
  ),
  'acf_field_column_names' => 
  array (
  ),
  'acf_field_column_name_patterns' => 
  array (
  ),
  'nested_field_key_parents' => 
  array (
  ),
  'column_owners' => 
  array (
    'post:course' => 
    array (
      'field_5cc2161e7b584' => '0.alma_id',
      'field_5cc05b642ba7f' => '0.course_code',
      'field_5dea86ee08933' => '0.academic_department_code',
      'field_5cc05bd1a9800' => '0.course_number',
      'field_5cc05bdda9801' => '0.course_section',
      'field_5cc05b722ba80' => '0.start_date',
      'field_5cc05b802ba81' => '0.end_date',
      'field_5cc201c1a852c' => '0.institution',
      'field_5cc201daa852d' => '0.academic_department',
      'field_5cc201f1a852e' => '0.degree_level',
      'field_5cc201f9a852f' => '0.course_term',
      'field_5d5d649bd2029' => '0.core_resources',
      'field_5cc326f90696b' => '0.related_courses_resources',
      'field_5e5819970fbfd' => '0.librarians',
    ),
    'user' => 
    array (
      'field_5cc9bff4b440d' => '1.alma_id',
      'field_5d1f5dfee5704' => '1.alternate_id',
      'field_5cc9c036b440f' => '1.primo_id',
      'field_5cc9c025b440e' => '1.google_id',
      'field_5cc9c079b4410' => '1.picture_url',
      'field_5cf696493f092' => '1.expiration_date',
      'field_5ccc5a589c115' => '1.bridge_library_institution',
      'field_5cc9c0c5b4412' => '1.courses',
      'field_5cc9c1abb4413' => '1.academic_departments',
      'field_5cfeb4860abf8' => '1.librarians',
      'field_5d52eb9a29516' => '1.circulation_data',
      'field_5cca170b17493' => '1.resources',
      'field_5d32106324c02' => '1.user_favorites',
      'field_5ccc9d5d6d7c7' => '1.primo_favorites',
      'field_5cdaf2d7d64af' => '1.courses_cache_updated',
      'field_5cdaf313d64b0' => '1.resources_cache_updated',
      'field_5cdaf323d64b1' => '1.primo_favorites_cache_updated',
      'field_5cfeb4a50abf9' => '1.librarians_cache_updated',
      'field_5d52eca029517' => '1.circulation_data_cache_updated',
    ),
  ),
);