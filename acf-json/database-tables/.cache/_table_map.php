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
          'name' => 'alma_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alma_id',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'course_code',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_code',
          ),
          'format' => '%s',
        ),
        4 => 
        array (
          'name' => 'course_number',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_number',
          ),
          'format' => '%s',
        ),
        5 => 
        array (
          'name' => 'course_section',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_section',
          ),
          'format' => '%s',
        ),
        6 => 
        array (
          'name' => 'start_date',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'start_date',
          ),
          'format' => '%s',
        ),
        7 => 
        array (
          'name' => 'end_date',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'end_date',
          ),
          'format' => '%s',
        ),
        8 => 
        array (
          'name' => 'institution',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'institution',
          ),
          'format' => '%s',
        ),
        9 => 
        array (
          'name' => 'academic_department',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_department',
          ),
          'format' => '%s',
        ),
        10 => 
        array (
          'name' => 'degree_level',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'degree_level',
          ),
          'format' => '%s',
        ),
        11 => 
        array (
          'name' => 'course_term',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'course_term',
          ),
          'format' => '%s',
        ),
        12 => 
        array (
          'name' => 'core_resources',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'core_resources',
          ),
          'format' => '%s',
        ),
        13 => 
        array (
          'name' => 'related_courses_resources',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'related_courses_resources',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => 'e1fd273f44c0501d403b68842e0d0c0c',
      'modified' => 1566402132,
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
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'user_id',
          ),
        ),
        2 => 
        array (
          'name' => 'alma_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alma_id',
          ),
          'format' => '%s',
        ),
        3 => 
        array (
          'name' => 'alternate_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'alternate_id',
          ),
          'format' => '%s',
        ),
        4 => 
        array (
          'name' => 'primo_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_id',
          ),
          'format' => '%s',
        ),
        5 => 
        array (
          'name' => 'google_id',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'google_id',
          ),
          'format' => '%s',
        ),
        6 => 
        array (
          'name' => 'picture_url',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'picture_url',
          ),
          'format' => '%s',
        ),
        7 => 
        array (
          'name' => 'expiration_date',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'expiration_date',
          ),
          'format' => '%s',
        ),
        8 => 
        array (
          'name' => 'bridge_library_institution',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'bridge_library_institution',
          ),
          'format' => '%s',
        ),
        9 => 
        array (
          'name' => 'courses',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'courses',
          ),
          'format' => '%s',
        ),
        10 => 
        array (
          'name' => 'academic_departments',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'academic_departments',
          ),
          'format' => '%s',
        ),
        11 => 
        array (
          'name' => 'librarians',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarians',
          ),
          'format' => '%s',
        ),
        12 => 
        array (
          'name' => 'circulation_data',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'circulation_data',
          ),
          'format' => '%s',
        ),
        13 => 
        array (
          'name' => 'resources',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resources',
          ),
          'format' => '%s',
        ),
        14 => 
        array (
          'name' => 'user_favorites',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'user_favorites',
          ),
          'format' => '%s',
        ),
        15 => 
        array (
          'name' => 'primo_favorites',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_favorites',
          ),
          'format' => '%s',
        ),
        16 => 
        array (
          'name' => 'courses_cache_updated',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'courses_cache_updated',
          ),
          'format' => '%s',
        ),
        17 => 
        array (
          'name' => 'resources_cache_updated',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resources_cache_updated',
          ),
          'format' => '%s',
        ),
        18 => 
        array (
          'name' => 'primo_favorites_cache_updated',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'primo_favorites_cache_updated',
          ),
          'format' => '%s',
        ),
        19 => 
        array (
          'name' => 'librarians_cache_updated',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'librarians_cache_updated',
          ),
          'format' => '%s',
        ),
        20 => 
        array (
          'name' => 'circulation_data_cache_updated',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'circulation_data_cache_updated',
          ),
          'format' => '%s',
        ),
      ),
      'hash' => 'a4750780407da5b99b11f6e2c8e2cb66',
      'modified' => 1565716740,
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
      'modified' => 1563896020,
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
          'name' => 'resource_format',
          'map' => 
          array (
            'type' => 'acf_field_name',
            'identifier' => 'resource_format',
          ),
          'format' => '%s',
        ),
        16 => 
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
      'hash' => 'e3233b9f1ab76afaf80b82d8b78d7a39',
      'modified' => 1567653246,
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
  'acf_field_column_names' => 
  array (
  ),
);