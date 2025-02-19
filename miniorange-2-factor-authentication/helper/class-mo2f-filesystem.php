<?php
/**
 * This file contains functions related to filesystem.
 *
 * @package miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;

if ( ! class_exists( 'Mo2f_Filesystem' ) ) {
	/**
	 *  Class contains methods for file system.
	 */
	class Mo2f_Filesystem {

		use Instance;
        
        /**
         * Writes a string to a file.
         *
         * @param string    $file     Remote path to the file where to write the data.
         * @param string    $contents The data to write.
         * @param int|false $mode     Optional. The file permissions as octal number, usually 0644.
         *                            Default false.
         * @return bool True on success, false on failure.
         */
        public function mo2f_put_contents( $file, $contents, $mode = false ) {
			$fp = @fopen( $file, 'wb' );
			if ( ! $fp ) {
				return false;
			}
			mbstring_binary_safe_encoding();
			$data_length = strlen( $contents );
			$bytes_written = fwrite( $fp, $contents );
			reset_mbstring_encoding();
			fclose( $fp );
			if ( $data_length !== $bytes_written ) {
				return false;
			}
			$this->mo2f_chmod( $file, $mode );
			return true;
		}
        
        /**
         * Changes filesystem permissions.
         *
         * @param string    $file      Path to the file.
         * @param int|false $mode      Optional. The permissions as octal number, usually 0644 for files,
         *                             0755 for directories. Default false.
         * @param bool      $recursive Optional. If set to true, changes file permissions recursively.
         *                             Default false.
         * @return bool True on success, false on failure.
         */
		public function mo2f_chmod( $file, $mode = false, $recursive = false ) {
			if ( ! $mode ) {
				if ( $this->mo2f_is_file( $file ) ) {
					$mode = FS_CHMOD_FILE;
				} elseif ( $this->mo2f_is_dir( $file ) ) {
					$mode = FS_CHMOD_DIR;
				} else {
					return false;
				}
			}
			if ( ! $recursive || ! $this->mo2f_is_dir( $file ) ) {
				return chmod( $file, $mode );
			}
			// Is a directory, and we want recursive.
			$file     = trailingslashit( $file );
			$filelist = $this->mo2f_dirlist( $file );
			foreach ( (array) $filelist as $filename => $filemeta ) {
				$this->mo2f_chmod( $file . $filename, $mode, $recursive );
			}
			return true;
		}

        /**
         * Checks if resource is a file.
         *
         * @param string $file File path.
         * @return bool Whether $file is a file.
         */
		public function mo2f_is_file( $file ) {
			return @is_file( $file );
		}

        /**
         * Checks if resource is a directory.
         *
         * @param string $path Directory path.
         * @return bool Whether $path is a directory.
         */
		public function is_dir( $path ) {
			return @is_dir( $path );
		}

        /**
         * Gets details for files in a directory or a specific file.
         *
         * @param string $path           Path to directory or file.
         * @param bool   $include_hidden Optional. Whether to include details of hidden ("." prefixed) files.
         *                               Default true.
         * @param bool   $recursive      Optional. Whether to recursively include file details in nested directories.
         *                               Default false.
         * @return array|false
         */
		public function mo2f_dirlist( $path, $include_hidden = true, $recursive = false ) {
			if ( $this->mo2f_is_file( $path ) ) {
				$limit_file = basename( $path );
				$path       = dirname( $path );
			} else {
				$limit_file = false;
			}
			if ( ! $this->mo2f_is_dir( $path ) || ! $this->mo2f_is_readable( $path ) ) {
				return false;
			}
			$dir = dir( $path );
			if ( ! $dir ) {
				return false;
			}
			$path = trailingslashit( $path );
			$ret  = array();
			while ( false !== ( $entry = $dir->read() ) ) {
				$struc         = array();
				$struc['name'] = $entry;
				if ( '.' === $struc['name'] || '..' === $struc['name'] ) {
					continue;
				}
				if ( ! $include_hidden && '.' === $struc['name'][0] ) {
					continue;
				}
				if ( $limit_file && $struc['name'] !== $limit_file ) {
					continue;
				}
				$struc['perms']       = $this->mo2f_gethchmod( $path . $entry );
				$struc['permsn']      = $this->mo2f_getnumchmodfromh( $struc['perms'] );
				$struc['number']      = false;
				$struc['owner']       = $this->mo2f_owner( $path . $entry );
				$struc['group']       = $this->mo2f_group( $path . $entry );
				$struc['size']        = $this->mo2f_size( $path . $entry );
				$struc['lastmodunix'] = $this->mo2f_mtime( $path . $entry );
				$struc['lastmod']     = gmdate( 'M j', $struc['lastmodunix'] );
				$struc['time']        = gmdate( 'h:i:s', $struc['lastmodunix'] );
				$struc['type']        = $this->mo2f_is_dir( $path . $entry ) ? 'd' : 'f';
				if ( 'd' === $struc['type'] ) {
					if ( $recursive ) {
						$struc['files'] = $this->mo2f_dirlist( $path . $struc['name'], $include_hidden, $recursive );
					} else {
						$struc['files'] = array();
					}
				}
		
				$ret[ $struc['name'] ] = $struc;
			}
			$dir->close();
			unset( $dir );
			return $ret;
		}

        /**
         * Checks if a file is readable.
         *
         * @param string $file Path to file.
         * @return bool Whether $file is readable.
         */
		public function mo2f_is_readable( $file ) {
			return @is_readable( $file );
		}


        /**
         * Checks if a file is writable.
         *
         * @param string $file Path to file.
         * @return bool Whether $file is writable.
         */
        public function mo2f_is_writable( $path ) {
            return @is_writable( $path );
        }

        /**
         * Gets the file modification time.
         *
         * @param string $file Path to file.
         * @return int|false Unix timestamp representing modification time, false on failure.
         */
		public function mo2f_mtime( $file ) {
			return @filemtime( $file );
		}

        /**
         * Gets the file size (in bytes).
         *
         * @param string $file Path to file.
         * @return int|false Size of the file in bytes on success, false on failure.
         */
		public function mo2f_size( $file ) {
			return @filesize( $file );
		}

        /**
         * Checks if resource is a directory.
         *
         * @param string $path Directory path.
         * @return bool Whether $path is a directory.
         */
        public function mo2f_is_dir( $path ) {
            return @is_dir( $path );
        }
        
        /**
         * Gets the file's group.
         *
         * @param string $file Path to the file.
         * @return mixed The group on success, false on failure.
         */
		public function mo2f_group( $file ) {
			$gid = @filegroup( $file );
			if ( ! $gid ) {
				return false;
			}
			if ( ! function_exists( 'posix_getgrgid' ) ) {
				return $gid;
			}
			$grouparray = posix_getgrgid( $gid );
		
			if ( ! $grouparray ) {
				return false;
			}
			return $grouparray['name'];
		}

        /**
         * Gets the file owner.
         *
         * @param string $file Path to the file.
         * @return mixed Username of the owner on success, false on failure.
         */
		public function mo2f_owner( $file ) {
			$owneruid = @fileowner( $file );
			if ( ! $owneruid ) {
				return false;
			}
			if ( ! function_exists( 'posix_getpwuid' ) ) {
				return $owneruid;
			}
			$ownerarray = posix_getpwuid( $owneruid );
			if ( ! $ownerarray ) {
				return false;
			}
			return $ownerarray['name'];
		}

        /**
         * Gets the permissions of the specified file or filepath in their octal format.
         *
         * @return string Mode of the file (the last 3 digits).
         */
        public function mo2f_getchmod() {
            return '777';
        }

        /**
         * Returns the *nix-style file permissions for a file.
         *
         * @param string $file String filename.
         * @return string The *nix-style representation of permissions.
         */
        public function mo2f_gethchmod( $file ) {
            $perms = intval( $this->mo2f_getchmod(), 8 );
            if ( ( $perms & 0xC000 ) === 0xC000 ) { // Socket.
                $info = 's';
            } elseif ( ( $perms & 0xA000 ) === 0xA000 ) { // Symbolic Link.
                $info = 'l';
            } elseif ( ( $perms & 0x8000 ) === 0x8000 ) { // Regular.
                $info = '-';
            } elseif ( ( $perms & 0x6000 ) === 0x6000 ) { // Block special.
                $info = 'b';
            } elseif ( ( $perms & 0x4000 ) === 0x4000 ) { // Directory.
                $info = 'd';
            } elseif ( ( $perms & 0x2000 ) === 0x2000 ) { // Character special.
                $info = 'c';
            } elseif ( ( $perms & 0x1000 ) === 0x1000 ) { // FIFO pipe.
                $info = 'p';
            } else { // Unknown.
                $info = 'u';
            }
            // Owner.
            $info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
            $info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
            $info .= ( ( $perms & 0x0040 ) ? ( ( $perms & 0x0800 ) ? 's' : 'x' ) : ( ( $perms & 0x0800 ) ? 'S' : '-' ) );
            // Group.
            $info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
            $info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
            $info .= ( ( $perms & 0x0008 ) ? ( ( $perms & 0x0400 ) ? 's' : 'x' ) : ( ( $perms & 0x0400 ) ? 'S' : '-' ) );
            // World.
            $info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
            $info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
            $info .= ( ( $perms & 0x0001 ) ? ( ( $perms & 0x0200 ) ? 't' : 'x' ) : ( ( $perms & 0x0200 ) ? 'T' : '-' ) );
            return $info;
        }

        /**
         * Converts *nix-style file permissions to an octal number.
         *
         * @param string $mode string The *nix-style file permissions.
         * @return string Octal representation of permissions.
         */
        public function mo2f_getnumchmodfromh( $mode ) {
            $realmode = '';
            $legal    = array( '', 'w', 'r', 'x', '-' );
            $attarray = preg_split( '//', $mode );
            for ( $i = 0, $c = count( $attarray ); $i < $c; $i++ ) {
                $key = array_search( $attarray[ $i ], $legal, true );

                if ( $key ) {
                    $realmode .= $legal[ $key ];
                }
            }
            $mode  = str_pad( $realmode, 10, '-', STR_PAD_LEFT );
            $trans = array(
                '-' => '0',
                'r' => '4',
                'w' => '2',
                'x' => '1',
            );
            $mode  = strtr( $mode, $trans );
            $newmode  = $mode[0];
            $newmode .= $mode[1] + $mode[2] + $mode[3];
            $newmode .= $mode[4] + $mode[5] + $mode[6];
            $newmode .= $mode[7] + $mode[8] + $mode[9];
            return $newmode;
        }
        
        /**
         * Fetches file content.
         *
         * @param string $file File.
         * @return mixed
         */
        public function mo2f_get_contents( $file ) {
            return @file_get_contents( $file );
        }
        
	}
}
