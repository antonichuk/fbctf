<?hh

require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

sess_start();
sess_enforce_login();

class CountryDataController extends DataController {
  public function generateData() {
    $levels = new Levels();
    $countries = new Countries();
    $attachments = new Attachments();
    $links = new Links();
    $teams = new Teams();

    $my_team = $teams->get_team(sess_team());

    $countries_data = (object) array();
    foreach ($levels->all_levels(1) as $level) {
      $country = $countries->get_country($level['entity_id']);
      if (!$country) {
        continue;
      }

      $category = $levels->get_category($level['category_id']);
      if ($level['hint']) {
        // There is hint, can this team afford it?
        if ($level['penalty'] > $my_team['points']) {
          $hint_cost = -1;
          $hint = 'no';
        } else {
          // Has this team requested this hint before?
          if ($levels->previous_hint($level['id'], $my_team['id'])) {
            $hint_cost = 0;
          } else {
            $hint_cost = $level['penalty'];
          }
          $hint = ($hint_cost == 0) ? $level['hint'] : 'yes';
        }
      } else {
        $hint_cost = -1;
        $hint = 'no';
      }

      // All attachments for this level
      $attachments_list = array();
      if ($attachments->has_attachments($level['id'])) {
        foreach ($attachments->all_attachments($level['id']) as $attachment) {
          array_push($attachments_list, $attachment['filename']);
        }
      }

      // All links for this level
      $links_list = array();
      if ($links->has_links($level['id'])) {
        foreach ($links->all_links($level['id']) as $link) {
          array_push($links_list, $link['link']);
        }
      }

      // All teams that have completed this level
      $completed_by = array();
      foreach ($levels->completed_by($level['id']) as $c) {
        array_push($completed_by, $c['name']);
      }

      // Who is the first owner of this level
      $owner = ($completed_by) ? $completed_by[0] : 'Uncaptured';
      $country_data = (object) array(
        'level_id' => $level['id'],
        'intro' => $level['description'],
        'type'  => $level['type'],
        'points' => (int) $level['points'],
        'bonus' => (int) $level['bonus'],
        'category' => $category['category'],
        'owner' => $owner,
        'completed' => $completed_by,
        'hint' => $hint,
        'hint_cost' => $hint_cost,
        'attachments' => $attachments_list,
        'links' => $links_list
      );
      $countries_data->{$country['name']} = $country_data;
    }

    $this->jsonSend($countries_data);
  }
}

$countryData = new CountryDataController();
$countryData->generateData();