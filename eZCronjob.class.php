<?php
include ('iniStruct.class.php');

/**
 * Class eZCronjob
 */
class eZCronjob
{
    /**
     * Constructor. Load INI config file
     */
    public function __construct( )
    {
        $this->ini = INIStruct::parse( 'config.ini', true );
        $this->php = $this->ini['ScriptSettings']['PHP'];

        if ( empty( $this->php ) )
            die( "Please specify the location of the PHP Command Line Interface binary in config.php\n" );

        $this->jobCommands = $this->ini['CronjobSettings']['Commands'];
    }

    /**
     * Generates the command to be launched
     * @param $search
     * @param $replace
     * @param $command
     * return string
     */
    private function generateCommand( $search, $replace, $command )
    {
        $search = array(
            '--PHP--',
            $search
        );

        $replace = array(
            $this->php,
            $replace
        );

        return str_replace( $search, $replace, $command );
    }

	/**
	 * Checks a commands exists in the site specific settings
	 * @params $siteID
	 * @params $type
	 * return boolean
	 */
    protected function siteHasExtraCommand( $siteID, $type )
    {
        if ( array_key_exists( 'Commands', $this->ini[$siteID] ) )
        {
            $siteExtraCommands = $this->ini[$siteID]['Commands'];
            if ( array_key_exists( $type, $siteExtraCommands ) )
                return true;
            else
                return false;

        }
        else
        {
            return false;
        }
    }

    /**
     * Runs a single job
     * @param $type
     * @param $path
     * @param $siteaccess
	 * @return boolean
     */
    public function runJob( $type, $siteID )
    {
        if ( array_key_exists( 'IgnoreCommands', $this->ini[$siteID] ) && in_array( $type, $this->ini[$siteID]['IgnoreCommands'] ) )
        {
            echo "Skipping command {$type}\n";
            return false;
        }

        $siteaccess = $this->ini[$siteID]['SiteAccess'];
        $path = $this->ini[$siteID]['Path'];
        $sudo = ( array_key_exists( 'Sudo', $this->ini[$siteID] ) ? $this->ini[$siteID]['Sudo'] : '' );

        // Find command in the global settings
        if ( array_key_exists( $type, $this->jobCommands ) )
        {
            $jobCommand = $this->jobCommands[$type];
        }
        // Find command in the site settings
        elseif ( $this->siteHasExtraCommand( $siteID, $type ) )
        {
            $jobCommand = $this->ini[$siteID]['Commands'][$type];
        }
        else
        {
            echo "** Command {$type} unknown **\n\n";
            return false;
        }

        if ( file_exists( $path ) )
        {
            echo "{$path}\n";
            chdir( $path );

            $command = $this->generateCommand( '--SITEACCESS--', $siteaccess, $jobCommand );
            
            if( !empty( $sudo ) )
            	$command = "sudo -u {$sudo} {$command}";
            	
            echo "{$command}\n";

            system( $command );
        }
        else
        {
            echo "** Path to this website docroot does not exist**\n\n";
        }

        return true;
    }

    /**
     * Run all jobs
     * @param $path
     * @param $siteaccess
     */
    public function runAllJobs( $siteID )
    {
        foreach ( $this->jobCommands as $type => $jobCommand )
            $this->runJob( $type, $siteID );
		
		if( array_key_exists( 'Commands', $this->ini[$siteID] ) )
		{
			foreach( $this->ini[$siteID]['Commands'] as $type => $jobCommand)
				$this->runJob( $type, $siteID );
		}
    }

    /**
     * Start the process
     * @param $jobType
     */
    public function start( $jobType )
    {
        foreach ( $this->ini['CronjobSettings']['AvailableSites'] as $siteID )
        {
            if ( array_key_exists( $siteID, $this->ini ) )
            {
                echo "BEGIN {$siteID}\n";

                if ( empty( $jobType ) )
                    $this->runAllJobs( $siteID );
                else
                    $this->runJob( $jobType, $siteID );

                echo "END\n\n";
            }
            else
            {
                echo "** The site {$siteID} is not configured in config.ini **\n\n";
            }
        }
    }

    public $ini;
    protected $php;
    protected $jobCommands;
}
?>