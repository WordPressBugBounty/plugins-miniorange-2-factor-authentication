<?php
/**
 * This file contains functions related to filesystem.
 *
 * @package miniorange-2-factor-authentication/helper
 */

namespace TwoFA\Helper;

use TwoFA\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Filesystem' ) ) {
	/**
	 *  Class contains methods for file system.
	 */
	class Mo2f_Filesystem {

		use Instance;

		/**
		 * Retrieves the WP_Filesystem instance.
		 *
		 * @return \WP_Filesystem_Base|false
		 */
		protected function mo2f_get_filesystem() {
			global $wp_filesystem;

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			if ( ! $wp_filesystem ) {
				WP_Filesystem();
			}

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem;
		}

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
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			$result = $wp_filesystem->put_contents( $file, $contents, $mode );

			if ( $result ) {
				$this->mo2f_chmod( $file, $mode );
			}

			return (bool) $result;
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
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			if ( ! $mode ) {
				if ( $wp_filesystem->is_file( $file ) ) {
					$mode = FS_CHMOD_FILE;
				} elseif ( $wp_filesystem->is_dir( $file ) ) {
					$mode = FS_CHMOD_DIR;
				} else {
					return false;
				}
			}

			return $wp_filesystem->chmod( $file, $mode, $recursive );
		}

		/**
		 * Checks if resource is a file.
		 *
		 * @param string $file File path.
		 * @return bool Whether $file is a file.
		 */
		public function mo2f_is_file( $file ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->is_file( $file );
		}

		/**
		 * Checks if resource is a directory.
		 *
		 * @param string $path Directory path.
		 * @return bool Whether $path is a directory.
		 */
		public function is_dir( $path ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->is_dir( $path );
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
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			if ( $wp_filesystem->is_file( $path ) ) {
				$limit_file = wp_basename( $path );
				$path       = dirname( $path );
			} else {
				$limit_file = false;
			}

			if ( ! $wp_filesystem->is_dir( $path ) || ! $wp_filesystem->is_readable( $path ) ) {
				return false;
			}

			$path    = trailingslashit( $path );
			$listing = $wp_filesystem->dirlist( $path, $include_hidden, $recursive );

			if ( ! is_array( $listing ) ) {
				return false;
			}

			if ( $limit_file ) {
				if ( isset( $listing[ $limit_file ] ) ) {
					return array( $limit_file => $listing[ $limit_file ] );
				}

				return false;
			}

			return $listing;
		}

		/**
		 * Checks if a file is readable.
		 *
		 * @param string $file Path to file.
		 * @return bool Whether $file is readable.
		 */
		public function mo2f_is_readable( $file ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->is_readable( $file );
		}


		/**
		 * Checks if a file is writable.
		 *
		 * @param string $path Path to file.
		 * @return bool Whether the path is writable.
		 */
		public function mo2f_is_writable( $path ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->is_writable( $path );
		}

		/**
		 * Gets the file modification time.
		 *
		 * @param string $file Path to file.
		 * @return int|false Unix timestamp representing modification time, false on failure.
		 */
		public function mo2f_mtime( $file ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->mtime( $file );
		}

		/**
		 * Gets the file size (in bytes).
		 *
		 * @param string $file Path to file.
		 * @return int|false Size of the file in bytes on success, false on failure.
		 */
		public function mo2f_size( $file ) {
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->size( $file );
		}

		/**
		 * Checks if resource is a directory.
		 *
		 * @param string $path Directory path.
		 * @return bool Whether $path is a directory.
		 */
		public function mo2f_is_dir( $path ) {
			return $this->is_dir( $path );
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
			$mode     = str_pad( $realmode, 10, '-', STR_PAD_LEFT );
			$trans    = array(
				'-' => '0',
				'r' => '4',
				'w' => '2',
				'x' => '1',
			);
			$mode     = strtr( $mode, $trans );
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
			$wp_filesystem = $this->mo2f_get_filesystem();

			if ( ! $wp_filesystem ) {
				return false;
			}

			return $wp_filesystem->get_contents( $file );
		}
	}
}
