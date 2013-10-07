<?php

	Class migration_234 extends Migration{

		static function run($function, $existing_version = null) {
			self::$existing_version = $existing_version;

			try{
				$canProceed = self::$function();

				return ($canProceed === false) ? false : true;
			}
			catch(DatabaseException $e) {
				Symphony::Log()->writeToLog('Could not complete upgrading. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
			catch(Exception $e){
				Symphony::Log()->writeToLog('Could not complete upgrading because of the following error: ' . $e->getMessage(), E_ERROR, true);

				return false;
			}
		}

		static function getVersion(){
			return '2.3.4RC1';
		}

		static function getReleaseNotes(){
			return 'http://getsymphony.com/download/releases/version/2.3.4/';
		}

		static function upgrade(){
			if(version_compare(self::$existing_version, '2.3.4beta1', '<=')) {
				// Detect mod_rewrite #1808
				try {
					$htaccess = file_get_contents(DOCROOT . '/.htaccess');

					if($htaccess !== false && !preg_match('/SetEnv HTTP_MOD_REWRITE No/', $htaccess)){
						$rewrite = '
<IfModule !mod_rewrite.c>
	SetEnv HTTP_MOD_REWRITE No
</IfModule>

<IfModule mod_rewrite.c>';

						$htaccess = str_replace('<IfModule mod_rewrite.c>', $rewrite, $htaccess);
						file_put_contents(DOCROOT . '/.htaccess', $htaccess);
					}
				}
				catch (Exception $ex) {}

				// Extend token field to enable more secure tokens
				try {
					Symphony::Database()->query('ALTER TABLE `tbl_forgotpass` CHANGE `token` `token` VARCHAR(16);');
				}
				catch (Exception $ex) {}
			}

			if(version_compare(self::$existing_version, '2.3.4beta2', '<=')) {
				// Extend session_id field for default Suhosin installs
				try {
					Symphony::Database()->query('ALTER TABLE `tbl_sessions` CHANGE `session` `session` VARCHAR(128);');
				}
				catch (Exception $ex) {}
			}

			// Update the version information
			Symphony::Configuration()->set('version', self::getVersion(), 'symphony');
			Symphony::Configuration()->set('useragent', 'Symphony/' . self::getVersion(), 'general');

			if(Symphony::Configuration()->write() === false) {
				throw new Exception('Failed to write configuration file, please check the file permissions.');
			}
			else {
				return true;
			}
		}

	}
