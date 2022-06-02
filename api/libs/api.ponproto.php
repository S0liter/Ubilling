<?php

/**
 * Basic PON OLTs devices collectors hardware abstraction layer prototype
 */
class PONProto {

    /**
     * Contains current HAL instance OLT parameters
     *
     * @var array
     */
    protected $oltParameters = array();

    /**
     * Contains available SNMP templates for OLT modelids
     *
     * @var array
     */
    protected $snmpTemplates = array();

    /**
     * Default ONU offline signal level
     *
     * @var int
     */
    protected $onuOfflineSignalLevel = '-9000';

    /**
     * SNMPHelper object instance
     *
     * @var object
     */
    protected $snmp = '';

    /**
     * Contains OLTData
     *
     * @var object
     */
    protected $olt = '';

    /**
     * Replicated paths from primary PONizer class. 
     * This is here only for legacy of manual data manipulations wit self::
     * instead of usage $this->olt abstraction in HAL libs.
     */
    const SIGCACHE_PATH = OLTData::SIGCACHE_PATH;
    const SIGCACHE_EXT = OLTData::SIGCACHE_EXT;
    const DISTCACHE_PATH = OLTData::DISTCACHE_PATH;
    const DISTCACHE_EXT = OLTData::DISTCACHE_EXT;
    const ONUCACHE_PATH = OLTData::ONUCACHE_PATH;
    const ONUCACHE_EXT = OLTData::ONUCACHE_EXT;
    const INTCACHE_PATH = OLTData::INTCACHE_PATH;
    const INTCACHE_EXT = OLTData::INTCACHE_EXT;
    const INTDESCRCACHE_EXT = OLTData::INTDESCRCACHE_EXT;
    const FDBCACHE_PATH = OLTData::FDBCACHE_PATH;
    const FDBCACHE_EXT = OLTData::FDBCACHE_EXT;
    const DEREGCACHE_PATH = OLTData::DEREGCACHE_PATH;
    const DEREGCACHE_EXT = OLTData::DEREGCACHE_EXT;
    const UPTIME_PATH = OLTData::UPTIME_PATH;
    const UPTIME_EXT = OLTData::UPTIME_EXT;
    const TEMPERATURE_PATH = OLTData::TEMPERATURE_PATH;
    const TEMPERATURE_EXT = OLTData::TEMPERATURE_EXT;
    const MACDEVIDCACHE_PATH = OLTData::MACDEVIDCACHE_PATH;
    const MACDEVIDCACHE_EXT = OLTData::MACDEVIDCACHE_EXT;
    const ONUSIG_PATH = OLTData::ONUSIG_PATH;

    /**
     * Other instance parameters
     */
    const SNMPCACHE = PONizer::SNMPCACHE;
    const SNMPPORT = PONizer::SNMPPORT;

    /**
     * Creates new PON poller/parser proto
     * 
     * @param array $oltParameters
     * @param array $snmpTemplates
     */
    public function __construct($oltParameters, $snmpTemplates) {
        $this->oltParameters = $oltParameters;
        $this->snmpTemplates = $snmpTemplates;
        $this->initSNMP();
        $this->initOltData();
    }

    /**
     * Creates single instance of SNMPHelper object
     *
     * @return void
     */
    protected function initSNMP() {
        $this->snmp = new SNMPHelper();
    }

    /**
     * Inits current OLT data abstraction layer for further usage
     */
    protected function initOltData() {
        $this->olt = new OLTData($this->oltParameters['ID']);
    }

    /**
     * Sets current instance ONU offline signal level
     * 
     * @param int $level
     * 
     * @return void
     */
    public function setOfflineSignal($level) {
        $this->onuOfflineSignalLevel = $level;
    }

    /**
     * Main data collector method placeholder
     * 
     * @return void
     */
    public function collect() {
        /**
         * Ab esse ad posse valet, a posse ad esse non valet consequentia
         */
    }

    /**
     * Performs signal preprocessing for sig/mac index arrays and stores it into cache
     *
     * @param int $oltid
     * @param array $sigIndex
     * @param array $macIndex
     * @param array $snmpTemplate
     *
     * @return void
     */
    protected function signalParse($oltid, $sigIndex, $macIndex, $snmpTemplate) {
        $oltid = vf($oltid, 3);
        $sigTmp = array();
        $macTmp = array();
        $result = array();
        $curDate = curdatetime();

//signal index preprocessing
        if ((!empty($sigIndex)) and ( !empty($macIndex))) {
            foreach ($sigIndex as $io => $eachsig) {
                $line = explode('=', $eachsig);
//signal is present
                if (isset($line[1])) {
                    $signalRaw = trim($line[1]); // signal level
                    $devIndex = trim($line[0]); // device index
                    if ($signalRaw == $snmpTemplate['DOWNVALUE']) {
                        $signalRaw = 'Offline';
                    } else {
                        if ($snmpTemplate['OFFSETMODE'] == 'div') {
                            if ($snmpTemplate['OFFSET']) {
                                if (is_numeric($signalRaw)) {
                                    $signalRaw = $signalRaw / $snmpTemplate['OFFSET'];
                                } else {
                                    $signalRaw = 'Fail';
                                }
                            }
                        }
                    }
                    $sigTmp[$devIndex] = $signalRaw;
                }
            }

//mac index preprocessing
            foreach ($macIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $macTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($macTmp)) {
                foreach ($macTmp as $devId => $eachMac) {
                    if (isset($sigTmp[$devId])) {
                        $signal = $sigTmp[$devId];
                        $result[$eachMac] = $signal;
//signal history filling
                        $historyFile = self::ONUSIG_PATH . md5($eachMac);
                        if ($signal == 'Offline') {
                            $signal = $this->onuOfflineSignalLevel; //over 9000 offline signal level :P
                        }
                        file_put_contents($historyFile, $curDate . ',' . $signal . "\n", FILE_APPEND);
                    }
                }

                //writing signals cache
                $this->olt->writeSignals($result);

                // saving macindex as MAC => devID
                $macTmp = array_flip($macTmp);
                $this->olt->writeMacIndex($macTmp);
            }
        }
    }

    /**
     * Parses & stores in cache OLT ONU distances
     *
     * @param int $oltid
     * @param array $distIndex
     * @param array $onuIndex
     *
     * @return void
     */
    protected function distanceParse($oltid, $distIndex, $onuIndex) {
        $oltid = vf($oltid, 3);
        $distTmp = array();
        $onuTmp = array();
        $result = array();
        $curDate = curdatetime();

//distance index preprocessing
        if ((!empty($distIndex)) and ( !empty($onuIndex))) {
            foreach ($distIndex as $io => $eachdist) {
                $line = explode('=', $eachdist);
//distance is present
                if (isset($line[1])) {
                    $distanceRaw = trim($line[1]); // distance
                    $devIndex = trim($line[0]); // device index
                    $distTmp[$devIndex] = $distanceRaw;
                }
            }

//mac index preprocessing
            foreach ($onuIndex as $io => $eachmac) {
                $line = explode('=', $eachmac);
//mac is present
                if (isset($line[1])) {
                    $macRaw = trim($line[1]); //mac address
                    $devIndex = trim($line[0]); //device index
                    $macRaw = str_replace(' ', ':', $macRaw);
                    $macRaw = strtolower($macRaw);
                    $onuTmp[$devIndex] = $macRaw;
                }
            }

//storing results
            if (!empty($onuTmp)) {
                foreach ($onuTmp as $devId => $eachMac) {
                    if (isset($distTmp[$devId])) {
                        $distance = $distTmp[$devId];
                        $result[$eachMac] = $distance;
                    }
                }
                $result = serialize($result);
                file_put_contents(self::DISTCACHE_PATH . $oltid . '_' . self::DISTCACHE_EXT, $result);
                $onuTmp = serialize($onuTmp);
                file_put_contents(self::ONUCACHE_PATH . $oltid . '_' . self::ONUCACHE_EXT, $onuTmp);
            }
        }
    }

    /**
     * Parses BDCom uptime data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $uptimeRaw
     *
     * @return void
     */
    protected function uptimeParse($oltid, $uptimeRaw) {
        if (!empty($uptimeRaw)) {
            $uptimeRaw = explode(')', $uptimeRaw);
            $uptimeRaw = $uptimeRaw[1];
            $this->olt->writeUptime($uptimeRaw);
        }
    }

    /**
     * Parses BDCom temperature data and saves it into uptime cache
     *
     * @param int $oltid
     * @param string $uptimeRaw
     *
     * @return void
     */
    protected function temperatureParse($oltid, $tempRaw) {
        if (!empty($tempRaw)) {
            $tempRaw = explode(':', $tempRaw);
            $tempRaw = $tempRaw[1];
            $this->olt->writeTemperature($tempRaw);
        }
    }

}
