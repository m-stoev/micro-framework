<?php

/**
 * The class give us information about seasons and weather
 * based on open weather map.
 * 
 * @author Miroslav Stoev
 * @package micro-framework
 */
trait Seasons
{

    /**
     * Get variables for display snow or leaves
     * 
     * @param (array) $user_place = array('place' => 'place', 'lat' => 'lat')
     * @return (array) $seasons_vars
     */
    protected function get_vars(array $user_place = [])
    {
        // default latitude for north hemisphere
        if (!$user_place) {
            $user_place = [
                'place' => 'BG,Vratsa',
                'lat' => 1
            ];
        }

        // default season variables
        $seasons_vars = [
            'falling'       => null,
            'season'        => null,
            'is_christmas'  => false,
        ];
        
        $weathercast    = array();
        $today          = time();
        $autumn         = $this->get_autumn_dates($user_place['lat']);
        $winter         = $this->get_winter_dates($user_place['lat']);
        $cookies        = filter_input_array(INPUT_COOKIE);

        if ( (isset($cookies['isWinterStarted']) and $cookies['isWinterStarted'] == 1)
            or ( $today > $winter['start'] and $today < $winter['end'])
        ) {
            $seasons_vars['season'] = 'winter';
        }
        elseif ($today > $autumn['start'] and $today < $autumn['end']) {
            $seasons_vars['falling'] = 'leaves';
            $seasons_vars['season'] = 'autumn';
        }

        try {
            $weathercast = file_get_contents(
                'http://api.openweathermap.org/data/2.5/weather?units=metric&q='
                    . $user_place['place'] . '&APPID=' // set your App ID here
            );
            $weathercast = json_decode($weathercast, true);
        }
        catch(Exception $e) {}

        if ($weathercast and $weathercast['cod'] != 404) {
            if (is_array($weathercast)) {
                // check for snow codes
                // for help: http://openweathermap.org/weather-conditions
                $snow_codes     = array(600, 601, 602, 611, 612, 615, 616, 620, 621, 622);
                $weather_code   = $weathercast['weather'][0]['id'];

                if (in_array($weather_code, $snow_codes)) {
                    $seasons_vars['falling']    = 'snow';
                    $seasons_vars['season']     = 'winter';

                    // if there is snow and still is autumn just put an coockie,
                    // to know winter is here :)
                    if ($seasons_vars['season'] != 'winter') {
                        setcookie('isWinterStarted', 1, 60 * 60 * 24 * 30, COOKIE_PATH, SERVER_NAME, false, false);
                    }
                }
                // if there are no snow conditions, but the current temp is < 1 deg
                // we will accept it is snow during autumn or spring, and will set cookie again
                elseif ($seasons_vars['season'] != 'winter') {
                    if ($weathercast['main']['temp'] < 1) {
                        setcookie('isWinterStarted', 1, time() + 60 * 60 * 24 * 30, COOKIE_PATH, SERVER_NAME, false, false);
                    }
                }
            }
        }

        // check for Christmas holidays
        if ( ((int) date('m') == 12 and (int) date('d') > 15)
            or ( (int) date('m') == 1 and (int) date('d') <= 5)
        ) {
            $seasons_vars['is_christmas'] = true;

            // if still no snow stop falling leaves
            if ($seasons_vars['falling'] == 'leaves') {
                $seasons_vars['falling'] = null;
            }
        }

        // for test and manual control:
        //	$seasons_vars['falling'] = 'snow';
        //	$seasons_vars['is_christmas'] = TRUE;
        //	$seasons_vars['season'] = 'autumn';
        //	var_dump($seasons_vars);
        //	var_dump($weathercast);
        // for test end

        return $seasons_vars;
    }

    private function get_spring_dates($lat) {
        // north
        if ($lat > 0) {
            return array(
                'start' => strtotime(date("Y") . "-03-22"),
                'end'   => strtotime(date("Y") . "-06-21"),
            );
        }
        // south
        else {
            return array(
                'start' => strtotime(date("Y") . "-09-23"),
                'end'   => strtotime(date("Y") . "-12-21"),
            );
        }
    }

    private function get_autumn_dates($lat) {
        // north
        if ($lat > 0) {
            return array(
                'start' => strtotime(date("Y") . "-09-22"),
                'end'   => strtotime(date("Y") . "-12-20"),
            );
        }
        // south
        else {
            return array(
                'start' => strtotime(date("Y") . "-03-21"),
                'end'   => strtotime(date("Y") . "-06-21"),
            );
        }
    }

    private function get_winter_dates($lat) {
        // north
        if ($lat > 0) {
            // fix because the winter continues in the next year
            if (date("m") == 12) {
                return array(
                    'start' => strtotime(date("Y") . "-12-21"),
                    'end'   => strtotime((date("Y") + 1) . "-03-21"), // get next year
                );
            }
            else {
                return array(
                    // get previous year
                    'start' => strtotime((date("Y") - 1) . "-12-21"),
                    'end'   => strtotime(date("Y") . "-03-21"),
                );
            }
        }
        // south
        else {
            return array(
                'start' => strtotime(date("Y") . "-06-22"),
                'end'   => strtotime(date("Y") . "-09-22"),
            );
        }
    }

}
