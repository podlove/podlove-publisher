<?php
use League\Csv\Writer;

add_action('rest_api_init', 'podlove_api_init'); 

function podlove_api_init() {
  $args = [
    'format' => [
      'sanitize_callback' => function($param, $request, $key) {
        return $param == 'csv' ? 'csv' : 'json';
      },
      'default' => 'json'
    ]
  ];

  register_rest_route('podlove/v1', 'analytics/episodes', [
    'methods' => 'GET',
    'callback' => 'podlove_api_analytics_episodes',
    'permission_callback' => 'podlove_api_analytics_permission_callback',
    'args' => $args
  ]);
  register_rest_route('podlove/v1', 'analytics/episodes/(?P<id>[\d]+)', [
    'methods' => 'GET',
    'callback' => 'podlove_api_analytics_episode',
    'permission_callback' => 'podlove_api_analytics_permission_callback',
    'args' => $args
  ]);
  register_rest_route('podlove/v1', 'analytics/episodes/(?P<ids>[\d]+,[\d,]+)', [
    'methods' => 'GET',
    'callback' => 'podlove_api_analytics_episodes_selected',
    'permission_callback' => 'podlove_api_analytics_permission_callback',
    'args' => $args
  ]);
}

function podlove_api_analytics_permission_callback($request) {
  if ( ! current_user_can( 'podlove_read_analytics' ) ) {
      return new WP_Error(
        'rest_forbidden',
        esc_html__( 'You cannot view the analytics resource.' ), 
        ['status' => podlove_api_authorization_status_code()]
      );
  }
  return true;
}
function podlove_api_authorization_status_code() {

    $status = 401;

    if ( is_user_logged_in() ) {
        $status = 403;
    }

    return $status;
}
function podlove_api_csv_response($data) {
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=podlove-episode-downloads.csv");

	$csv = Writer::createFromFileObject(new \SplTempFileObject());
	$csv->setEncodingFrom("utf-8");

	$headers = array_keys($data[0]);
	$csv->insertOne($headers);

  $csv->insertAll($data);
  
  echo $csv;
  exit;
}

function podlove_api_analytics_episodes(WP_REST_Request $request) {
  $data = \Podlove\Downloads_List_Data::get_data();

  if ($request->get_param('format') == "csv") {
    podlove_api_csv_response($data);
  } else {
    $data = array_map('podlove_api_analytics_prepare_episode', $data);
    return new WP_REST_Response($data);
  }
}

function podlove_api_analytics_episodes_selected(WP_REST_Request $request) {
  $ids = explode(",", $request["ids"]);
  $ids = array_map(function ($id) {
    return (int) trim($id);
  }, $ids);

  $data = \Podlove\Downloads_List_Data::get_data();
 
  $data = array_filter($data, function ($row) use ($ids) {
    return in_array($row['post_id'], $ids);
  });
  $data = array_values($data);

  if ($request->get_param('format') == "csv") {
    podlove_api_csv_response($data);
  } else {
    $data = array_map('podlove_api_analytics_prepare_episode', $data);
    return new WP_REST_Response($data);
  }
}

function podlove_api_analytics_episode(WP_REST_Request $request) {
  $id = (int) $request['id'];
  $post = get_post($id);
 
  if (empty($post)) {
      return new WP_REST_Response([], 404);
  }

  $data = \Podlove\Downloads_List_Data::get_data();
  $data = array_map('podlove_api_analytics_prepare_episode', $data);
  
  $data = array_filter($data, function ($row) use ($id) {
    return $row['post_id'] == $id;
  });
  $data = array_values($data);

  if ($request->get_param('format') == "csv") {
    podlove_api_csv_response($data);
  } else {
    $data = array_map('podlove_api_analytics_prepare_episode', $data);
    return new WP_REST_Response($data[0]);
  }
}

function podlove_api_analytics_prepare_episode($item) {
  $item['_links'] = [
    'self' => rest_url('podlove/v1/analytics/episodes/' . $item['post_id']),
    'podlove:episode' => rest_url('wp/v2/episodes/' . $item['post_id'])
  ];

  return $item;
}
