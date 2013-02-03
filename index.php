<?php
/**
 * Oyster Push Notifications
 *
 * Dependencies:
 *  oyster-journey: Oyster website scrapper
 *      @author Ollie Read <me@ollieread.com>
 *      Url: https://github.com/ollieread/oyster-journey
 *
 *  Boxcar API PHP wrapper: A simple boxcar provider implementation for PHP
 *      @author Russell Smith <russell.smith@ukd1.co.uk>
 *      Url: https://github.com/ukd1/Boxcar
 *
 * History:
 *
 *		03-Feb-2013
 *			First version.
 *
 * @version 0.1
 * @author Juan Carlos Alonso <me@jcalonso.com>
 * @license MIT license
 * @see https://github.com/jcalonso/Oyster-Push-Notifications
 */
require_once( 'config.php' );
require_once( 'OysterJourney.php' );
require_once( 'class.MySQL.php' );
require_once( 'boxcar_api.php' );

try {
    // grab it
    $OysterJourney = new OysterJourney();
    // check its status
    if($OysterJourney->didItWork() === true) {
        // Get the journey info
        $data = $OysterJourney->getJourney();

        // Save to DB
        $journeysInserted = addJourneysToDb( $data );

        // Print results
        echo json_encode( array( 'journeys inserted:' => $journeysInserted, 'days filtered' => sizeof( $data ) ) );

    }
} catch(Exception $e) {
    // something went wrong
    echo $e->getMessage();
}

function addJourneysToDb( $data ) {

    $Db = new mysql( DB_NAME, DB_USER, DB_PASS, DB_HOST);

    $journeysInserted = 0;

    foreach ( $data as $days ) {
        foreach ( $days['journeys'] as $journeys ) {

            // Format the data
            $timeStamp = strtotime( $days['date'] );
            $date = date( 'Y-m-d', $timeStamp );

            $total = $days['total'];

            $timeStamp = strtotime($date . $journeys['time']['start'] );
            $startTime = date( 'Y-m-d H:i:s',$timeStamp );

            $timeStamp = strtotime($date . $journeys['time']['end'] );
            $endTime = date( 'Y-m-d H:i:s' ,$timeStamp );

            $startStation = $journeys['stations']['start'];

            $endStation = $journeys['stations']['end'];

            $charge = $journeys['charge'];

            $balance = $journeys['balance'];

            $journeyData = array(
                'cardID' => OYSTER_CARDID,
                'total' => $total,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'startStation' => $startStation,
                'endStation' => $endStation,
                'charge' => $charge,
                'balance' => $balance);

            // Check if we have this registry in DB (timestap is going to be our reference)
            if( !is_array( $Db->Select( 'journeys', array('startTime' => $startTime) ) ) ) {
                // Try to insert the data into the DB
                if( !$Db->Insert($journeyData, 'journeys') ) {
                    echo 'problem inserting!';
                }
                else{
                    $journeysInserted++;

                    // Send push notification
                    $body   = "Trip from: " . $startStation
                            . "\nto: " . $endStation
                            . "\non: " . $days['date']
                            . "\ncharge: £" . $charge
                            . "\nbalance: £" . $balance;

                    sendPushNotification( 'New journey registered', $body );
                }
            }
        }
    }

    return $journeysInserted;
}

function sendPushNotification( $title, $message ){
    //Send push notification

    // this is needed to stop warnings when using the date functions
    date_default_timezone_set( 'Europe/London' );

    // instantiate a new instance of the boxcar api
    $b = new boxcar_api( BOXCAR_API_KEY , BOXCAR_API_SEC, APP_PATH.'oysterIcon.gif' );

    // send a broadcast (to all your subscribers)
    $b->broadcast( $title, $message );

    return true;
}

