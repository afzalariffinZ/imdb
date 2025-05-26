<?php

require_once 'connection.php';
require_once './objects/ArrayValue.php';
require_once './objects/Title.php';

/**
 * Create connection to the database
 *
 * @return PDO (PHP Data Objects) provides access to the database
 */
function openConnection(): PDO
{
    // // --- DEBUG: Check the DB path ---
    // $dsn = CONNECTION_STRING; // Get the DSN from your constants in connection.php
    // $resolved_path_for_logging = ''; // For logging what path we think we're using

    // if (stripos($dsn, 'sqlite:') === 0) {
    //     $db_file_path_from_dsn = substr($dsn, strlen('sqlite:'));
    //     $resolved_path_for_logging = $db_file_path_from_dsn; // Default to DSN path

    //     // Try to get a canonical absolute path for logging and for a preliminary file_exists check
    //     $temp_resolved_path = realpath($db_file_path_from_dsn);

    //     if ($temp_resolved_path !== false) {
    //         $resolved_path_for_logging = $temp_resolved_path; // Use the resolved path for logging
    //         error_log("openConnection DEBUG: SQLite path from DSN '$db_file_path_from_dsn' resolved by realpath() to: '$resolved_path_for_logging'");
    //         if (!file_exists($resolved_path_for_logging)) {
    //             // This should not happen if realpath succeeded, but good to double check
    //             error_log("openConnection DEBUG: WARNING - realpath() succeeded but file_exists() failed for resolved path: '$resolved_path_for_logging'");
    //         } else {
    //             error_log("openConnection DEBUG: File confirmed to exist by file_exists() at resolved path: '$resolved_path_for_logging'");
    //         }
    //     } else {
    //         // realpath failed. This can happen if the path doesn't exist or if PHP doesn't have permissions
    //         // to access parts of the path. For relative paths, it's relative to getcwd().
    //         $cwd = getcwd();
    //         error_log("openConnection DEBUG: realpath() failed for DSN path '$db_file_path_from_dsn'. This might mean the file/path doesn't exist or isn't accessible from CWD '$cwd'. PDO will attempt to connect using the DSN path directly: '$dsn'");
    //         // We'll still try to connect using the DSN string, PDO might handle it.
    //         // For a preliminary file_exists check with a relative path if realpath failed:
    //         if (file_exists($db_file_path_from_dsn)) {
    //              error_log("openConnection DEBUG: file_exists() check on DSN path '$db_file_path_from_dsn' (relative to CWD '$cwd') is TRUE.");
    //         } else {
    //              error_log("openConnection DEBUG: file_exists() check on DSN path '$db_file_path_from_dsn' (relative to CWD '$cwd') is FALSE.");
    //         }
    //     }
    // } else {
    //     error_log("openConnection DEBUG: DSN is not for SQLite: '$dsn'");
    // }
    // // --- END DEBUG ---

    try {
        // Use the original DSN for connection as defined in connection.php
        $pdo = new PDO(
            CONNECTION_STRING,
            CONNECTION_USER,     // Usually null for SQLite
            CONNECTION_PASSWORD, // Usually null for SQLite
            CONNECTION_OPTIONS   // Should include PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ); // Wait up to 5000ms (5 seconds)
        
        // If connection is successful, and it's SQLite, let's confirm the attached DB path
        // if (stripos(CONNECTION_STRING, 'sqlite:') === 0) {
        //     $stmt = $pdo->query("PRAGMA database_list;");
        //     $db_list = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use default fetch mode from CONNECTION_OPTIONS
        //     if (!empty($db_list) && isset($db_list[0]['file'])) {
        //         $attached_file = $db_list[0]['file'];
        //         error_log("openConnection DEBUG: PDO connection successful. Attached database file (from PRAGMA database_list): '" . $attached_file . "'");
        //         if ($attached_file === '') {
        //              error_log("openConnection DEBUG: CRITICAL WARNING - Attached database file path is EMPTY. This means PDO likely created a new, empty IN-MEMORY database because the file specified in DSN ('$db_file_path_from_dsn') was not found or was not writable/readable by PDO. Your queries will run on an empty DB!");
        //         } else if ($resolved_path_for_logging && str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $attached_file) !== str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $resolved_path_for_logging) && $temp_resolved_path !== false) {
        //              error_log("openConnection DEBUG: MISMATCH WARNING - Resolved path for logging was '$resolved_path_for_logging' but PRAGMA reports attached file as '$attached_file'. Check paths carefully.");
        //         }
        //     } else {
        //         error_log("openConnection DEBUG: PDO connection successful, but could not confirm attached SQLite file path via PRAGMA database_list for DSN: " . CONNECTION_STRING);
        //     }
        // } else {
        //      error_log("openConnection DEBUG: PDO connection successful to non-SQLite DSN: " . CONNECTION_STRING);
        // }

    } catch (PDOException $e) {
        error_log("openConnection DEBUG: PDO Connection FAILED. DSN: " . CONNECTION_STRING . " Error: " . $e->getMessage());
        // If connection fails, also log the path it tried to use for SQLite
        if (stripos(CONNECTION_STRING, 'sqlite:') === 0 && isset($db_file_path_from_dsn)) { // check if $db_file_path_from_dsn was set
            error_log("openConnection DEBUG: Attempted to connect to SQLite file (from DSN): '$db_file_path_from_dsn'");
        }
        throw $e; // Re-throw the exception so the calling code (getMovies) can catch it if it wants
    }

    return $pdo;
}

// function getTitles($offset, $limit, $title /* Define more parameters for filtering, e.g. rating, date, etc. */ )
// {
//     // WARNING! This is a slow query because it contains subqueries.
//     // It would be better implemented a separate queries specific to any given (filtering, pagination) purpose.
//     $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title, 
//                      originalTitle as original_title, isAdult as is_adult, startYear as start_year, 
//                      endYear as end_year, runtimeMinutes as runtime_minutes, t.genres, 
//                      r.averageRating as rating, r.numVotes as votes,
//                      (
//                          SELECT count(*)
//                          FROM title_director_trim d
//                          WHERE d.tconst = t.tconst
//                      ) as directors_count,
//                      (
//                          SELECT count(*)
//                          FROM title_principals_trim p
//                          WHERE p.tconst = t.tconst
//                      ) as principals_count,
//                      (
//                          SELECT count(*)
//                          FROM title_writer_trim w
//                          WHERE w.tconst = t.tconst
//                      ) as writers_count
//               FROM title_basics_trim t
//               JOIN title_ratings_trim r on r.tconst = t.tconst
//               WHERE 1 = 1 "; // This allows us to tack on filtering and sorting and limiting clauses later on.

//     if (!empty($title)) {
//         $query .= "AND (primaryTitle LIKE :title or originalTitle LIKE :title) ";
//     }

//     $query .= "LIMIT :limit OFFSET :offset";

//     try {
//         $imdb = openConnection();
//         $stmt = $imdb->prepare($query);

//         if (!empty($title)) {
//             $title = "%" . $title . "%";
//             $stmt->bindParam(':title', $title, PDO::PARAM_STR);
//         }

//         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
//         $stmt->execute();
//         $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
//     } catch (PDOException $e) {
//         die($e->getMessage());
//     }
//     return $objects;
// }

// function getTitleCount($title)
// {
//     $query = "SELECT count(*) AS title_count
//               FROM title_basics_trim AS t
//               JOIN title_ratings_trim r on r.tconst = t.tconst
//               WHERE 1 = 1 ";

//     if (!empty($title)) {
//         $query = $query . "AND (primaryTitle LIKE :title or originalTitle LIKE :title) ";
//     }

//     try {
//         $db = openConnection();
//         $stmt = $db->prepare($query);

//         if (!empty($title)) {
//             $title = "%" . $title . "%";
//             $stmt->bindParam(':title', $title, PDO::PARAM_STR);
//         }

//         $stmt->execute();
//         $row = $stmt->fetch();

//     } catch (PDOException $e) {
//         die($e->getMessage());
//     }

//     return $row["title_count"];
// }

function getTitle($id) {
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title,
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year,
                     endYear as end_year, runtimeMinutes as runtime_minutes, r.averageRating as rating,
                     numVotes as votes,
                     (
                         SELECT count(*)
                         FROM title_director_trim d
                         WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*)
                         FROM title_principals_trim p
                         WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*)
                         FROM title_writer_trim w
                         WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.tconst = :id";

    try {
        $imdb = openConnection();
        $stmt = $imdb->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);

        $stmt->execute();
        $object = $stmt->fetchObject(Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    return $object;
}

function getTitleCount($title, $type) {
    $query = "SELECT COUNT(*) AS movie_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r ON r.tconst = t.tconst
              WHERE t.titleType = :type"; // Use :type to filter by title type

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        if (!empty($title)) {
            $search = "%" . $title . "%";
            $stmt->bindParam(':title', $search, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $row['movie_count'];
}

require_once __DIR__ . '/PosterFetcher.php'; // Or its correct path


function getMovie($id) {
    // Ensure aliases match Title class protected properties
    // ADDED t.image_url to SELECT
    $query = "SELECT t.tconst as id, 
                     t.image_url,  -- <<< ADD THIS LINE
                     t.titleType as title_type, 
                     t.primaryTitle as primary_title,
                     t.originalTitle as original_title, 
                     t.isAdult as is_adult, 
                     t.startYear as start_year,
                     t.endYear as end_year, 
                     t.runtimeMinutes as runtime_minutes,
                     t.genres as genres,
                     r.averageRating as rating,
                     r.numVotes as votes,
                     (SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst) as directors_count,
                     (SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst) as principals_count,
                     (SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst) as writers_count
              FROM title_basics_trim t
              LEFT JOIN title_ratings_trim r on r.tconst = t.tconst -- Use LEFT JOIN to get titles even without ratings
              WHERE t.tconst = :id"; // Removed t.titleType = 'movie' to make it generic for any title ID

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        // PDO::FETCH_PROPS_LATE allows constructor to run before properties are set
        $object = $stmt->fetchObject(Title::class); // Properties (including image_url) set by PDO
    } catch (PDOException $e) {
        error_log("Database error in getMovie for ID $id: " . $e->getMessage());
        return null;
    }
    return $object ?: null; // Return null if no object found
}


// In database.php

// ... (require_once for Title.php, connection.php, etc.)
// ... (openConnection() function)

// function getMovies($offset, $limit, $title_str) {
//     // EXTREMELY SIMPLIFIED QUERY FOR DEBUGGING
//     // This query deliberately DOES NOT select t.image_url yet.
//     // It uses an INNER JOIN like getMoviesCount.
//     $query = "SELECT t.tconst as id,
//                      t.titleType as title_type,
//                      t.primaryTitle as primary_title,
//                      t.originalTitle as original_title,
//                      t.isAdult as is_adult,
//                      t.startYear as start_year,
//                      t.endYear as end_year,
//                      t.runtimeMinutes as runtime_minutes,
//                      t.genres as genres,
//                      r.averageRating as rating,
//                      r.numVotes as votes,
//                      (SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst) as directors_count,
//                      (SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst) as principals_count,
//                      (SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst) as writers_count
//               FROM title_basics_trim t
//               JOIN title_ratings_trim r ON t.tconst = r.tconst -- INNER JOIN
//               WHERE t.titleType = 'movie'";

//     if (!empty($title_str)) {
//         $query .= " AND (t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
//     }

//     // Simple ORDER BY for now
//     $query .= " ORDER BY t.primaryTitle ASC ";
//     $query .= " LIMIT :limit OFFSET :offset";

//     // -- START DEBUG LOGGING (Still essential) --
//     error_log("--- SIMPLIFIED getMovies DEBUG START ---");
//     error_log("Simplified getMovies called with: offset=$offset, limit=$limit, title_str='$title_str'");
//     error_log("Simplified getMovies SQL Query: " . $query);
//     // -- END DEBUG LOGGING --

//     $objects = [];
//     try {
//         $db = openConnection();
//         if (!$db) {
//             error_log("Simplified getMovies: openConnection() failed or returned null. CRITICAL ERROR.");
//             return [];
//         }
//         error_log("Simplified getMovies: Database connection obtained.");

//         $stmt = $db->prepare($query);
//         if (!$stmt) {
//             error_log("Simplified getMovies: db->prepare() failed. DB Error Info: " . print_r($db->errorInfo(), true) . ". CRITICAL ERROR.");
//             return [];
//         }
//         error_log("Simplified getMovies: Statement prepared successfully.");

//         if (!empty($title_str)) {
//             $search = "%" . $title_str . "%";
//             $stmt->bindParam(':title_search', $search, PDO::PARAM_STR);
//             error_log("Simplified getMovies: Binding :title_search with value: $search");
//         }

//         $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
//         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
//         error_log("Simplified getMovies: Binding :limit with value: $limit (type INT), :offset with value: $offset (type INT)");

//         $executeResult = $stmt->execute();

//         if (!$executeResult) {
//             error_log("Simplified getMovies: Statement execution FAILED. Statement Error Info: " . print_r($stmt->errorInfo(), true));
//             return [];
//         }
//         error_log("Simplified getMovies: Statement executed SUCCESSFULLY.");

//         $rowCount = $stmt->rowCount();
//         error_log("Simplified getMovies: stmt->rowCount() after execute: " . $rowCount);

//         if ($rowCount > 0) {
//             $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
//             error_log("Simplified getMovies: Fetched " . count($objects) . " objects using FETCH_CLASS.");
//             if (count($objects) === 0 && $rowCount > 0) {
//                  error_log("Simplified getMovies: WARNING - rowCount was $rowCount but fetchAll(PDO::FETCH_CLASS) returned 0 objects.");
//             }
//         } else {
//             error_log("Simplified getMovies: No rows returned by the query (rowCount is 0).");
//         }

//     } catch (PDOException $e) {
//         error_log("Database error in Simplified getMovies (PDOException): " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
//         error_log("Failed query was: " . $query);
//         return [];
//     } catch (Throwable $th) {
//         error_log("General error in Simplified getMovies (Throwable): " . $th->getMessage());
//         error_log("Failed query was: " . $query);
//         return [];
//     }
//     error_log("Simplified getMovies: Returning " . count($objects) . " objects.");
//     error_log("--- SIMPLIFIED getMovies DEBUG END ---");
//     return $objects;
// }


// Example modification for getMovies (list) (this works)
function getMovies($offset, $limit, $title_str) { // Changed param name from $title to $title_str for clarity
    $query = "SELECT t.tconst as id, 
                    t.image_url,  /* Ensure this is selected */
                     t.titleType as title_type, 
                     t.primaryTitle as primary_title, 
                     t.originalTitle as original_title, 
                     t.isAdult as is_adult, 
                     t.startYear as start_year, 
                     t.endYear as end_year, 
                     t.runtimeMinutes as runtime_minutes, /* Correct alias */
                     t.genres as genres, /* Ensure this is selected */
                     r.averageRating as rating, 
                     r.numVotes as votes,
                     (SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst) as directors_count,
                     (SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst) as principals_count,
                     (SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.titleType = 'movie'"; // Or other types

    if (!empty($title_str)) {
        $query .= " AND (t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
    }
    $query .= " ORDER BY r.numVotes DESC, t.startYear DESC "; // Example ordering
    $query .= " LIMIT :limit OFFSET :offset";

    $objects = [];
    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title_str)) {
            $search = "%" . $title_str . "%";
            $stmt->bindParam(':title_search', $search, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
        // NO PosterFetcher CALLS HERE. Title::getImageUrl() will handle it for each object when toHtml() is called.

    } catch (PDOException $e) {
        error_log("Database error in getMovies: " . $e->getMessage());
    }
    return $objects; // Returns array of Title objects, or empty array on failure/no results
}

function getAll($offset, $limit, $title_str) { // Changed param name from $title to $title_str for clarity
    $query = "SELECT t.tconst as id, 
                    t.image_url,  /* Ensure this is selected */
                     t.titleType as title_type, 
                     t.primaryTitle as primary_title, 
                     t.originalTitle as original_title, 
                     t.isAdult as is_adult, 
                     t.startYear as start_year, 
                     t.endYear as end_year, 
                     t.runtimeMinutes as runtime_minutes, /* Correct alias */
                     t.genres as genres, /* Ensure this is selected */
                     r.averageRating as rating, 
                     r.numVotes as votes,
                     (SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst) as directors_count,
                     (SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst) as principals_count,
                     (SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst";

    $whereClauses = [];
    if (!empty($title_str)) {
        $whereClauses[] = "(t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
    }

    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }
    $query .= " ORDER BY r.numVotes DESC, t.startYear DESC "; // Example ordering
    $query .= " LIMIT :limit OFFSET :offset";

    $objects = [];
    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        

        if (!empty($title_str)) {
            $search = "%" . $title_str . "%";
            $stmt->bindParam(':title_search', $search, PDO::PARAM_STR);
        }

        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
        // NO PosterFetcher CALLS HERE. Title::getImageUrl() will handle it for each object when toHtml() is called.

    } catch (PDOException $e) {
        error_log("Database error in getMovies: " . $e->getMessage());
    }
    return $objects; // Returns array of Title objects, or empty array on failure/no results
}

function getAllCounts($title) {
    $query = "SELECT COUNT(*) AS movie_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r ON r.tconst = t.tconst
              WHERE t.titleType = 'movie'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $search = "%" . $title . "%";
            $stmt->bindParam(':title', $search, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $row['movie_count'];
}

function getTitles($offset, $limit, $title_str, $type) { // Changed param name from $title to $title_str for clarity
    $query = "SELECT t.tconst as id, 
                    t.image_url,  /* Ensure this is selected */
                     t.titleType as title_type, 
                     t.primaryTitle as primary_title, 
                     t.originalTitle as original_title, 
                     t.isAdult as is_adult, 
                     t.startYear as start_year, 
                     t.endYear as end_year, 
                     t.runtimeMinutes as runtime_minutes, /* Correct alias */
                     t.genres as genres, /* Ensure this is selected */
                     r.averageRating as rating, 
                     r.numVotes as votes,
                     (SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst) as directors_count,
                     (SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst) as principals_count,
                     (SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.titleType = :type"; // Or other types

    if (!empty($title_str)) {
        $query .= " AND (t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
    }
    $query .= " ORDER BY r.numVotes DESC, t.startYear DESC "; // Example ordering
    $query .= " LIMIT :limit OFFSET :offset";

    $objects = [];
    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        

        if (!empty($title_str)) {
            $search = "%" . $title_str . "%";
            $stmt->bindParam(':title_search', $search, PDO::PARAM_STR);
        }

        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
        // NO PosterFetcher CALLS HERE. Title::getImageUrl() will handle it for each object when toHtml() is called.

    } catch (PDOException $e) {
        error_log("Database error in getMovies: " . $e->getMessage());
    }
    return $objects; // Returns array of Title objects, or empty array on failure/no results
}


// function getMovies($offset, $limit, $title_str) {
//     $query = "SELECT
//                 t.tconst as id,
//                 t.image_url,
//                 t.titleType as title_type,
//                 t.primaryTitle as primary_title,
//                 t.originalTitle as original_title,
//                 t.isAdult as is_adult,
//                 t.startYear as start_year,
//                 t.endYear as end_year,
//                 t.runtimeMinutes as runtime_minutes,
//                 t.genres as genres,
//                 r.averageRating as rating,
//                 r.numVotes as votes,
//                 COUNT(DISTINCT td.nconst) as directors_count,
//                 COUNT(DISTINCT tp.nconst) as principals_count,
//                 COUNT(DISTINCT tw.nconst) as writers_count
//               FROM
//                 title_basics_trim t
//               INNER JOIN
//                 title_ratings_trim r ON r.tconst = t.tconst
//               LEFT JOIN
//                 title_principals_trim td ON td.tconst = t.tconst AND td.category = 'director' -- Assuming directors are in principals with category
//               LEFT JOIN
//                 title_principals_trim tp ON tp.tconst = t.tconst AND tp.category IN ('actor', 'actress', 'self') -- Common principal categories
//               LEFT JOIN
//                 title_principals_trim tw ON tw.tconst = t.tconst AND tw.category = 'writer'   -- Assuming writers are in principals with category
//               WHERE
//                 t.titleType = 'movie'";

//     // Note on JOINs above:
//     // It's common for IMDb data (like title.principals.tsv) to store directors, writers, actors, etc.,
//     // all in ONE table (e.g., title_principals_trim) distinguished by a 'category' column.
//     // If you have *separate* tables like title_director_trim and title_writer_trim,
//     // you would join to those directly instead of joining title_principals_trim three times.
//     // Example if you have separate tables:
//     // LEFT JOIN title_director_trim real_td ON real_td.tconst = t.tconst
//     // LEFT JOIN title_writer_trim real_tw ON real_tw.tconst = t.tconst
//     // And then COUNT(DISTINCT real_td.nconst), COUNT(DISTINCT real_tw.nconst)

//     $params = [];

//     if (!empty($title_str)) {
//         $query .= " AND (t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
//         $params[':title_search'] = "%" . $title_str . "%";
//     }

//     $query .= " GROUP BY
//                 t.tconst, t.image_url, t.titleType, t.primaryTitle, t.originalTitle,
//                 t.isAdult, t.startYear, t.endYear, t.runtimeMinutes, t.genres,
//                 r.averageRating, r.numVotes";

//     $query .= " ORDER BY r.numVotes DESC, t.startYear DESC, t.primaryTitle ASC ";
//     $query .= " LIMIT :limit OFFSET :offset";

//     // -- DEBUGGING --
//     error_log("--- OPTIMIZED getMovies (v4) DEBUG START ---");
//     error_log("Optimized getMovies (v4) SQL Query: " . $query);
//     if (!empty($params)) { error_log("Optimized getMovies (v4) Params: " . print_r($params, true)); }
//     // -- END DEBUGGING --

//     $objects = [];
//     try {
//         $db = openConnection();
//         $stmt = $db->prepare($query);

//         if (!empty($title_str)) {
//             $stmt->bindParam(':title_search', $params[':title_search'], PDO::PARAM_STR);
//         }
//         $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
//         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

//         $executeResult = $stmt->execute();
//         $rowCount = $stmt->rowCount();
//         error_log("Optimized getMovies (v4): stmt->rowCount() after execute: " . $rowCount);

//         if ($executeResult && $rowCount > 0) {
//             $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
//             error_log("Optimized getMovies (v4): Fetched " . count($objects) . " objects.");
//         } elseif (!$executeResult) {
//              error_log("Optimized getMovies (v4): Statement execution FAILED. Stmt Error Info: " . print_r($stmt->errorInfo(), true));
//         } else {
//             error_log("Optimized getMovies (v4): No rows returned by query (rowCount is 0).");
//         }

//     } catch (PDOException $e) {
//         error_log("Database error in Optimized getMovies (v4) (PDOException): " . $e->getMessage());
//         error_log("Failed query was: " . $query . " Params: " . print_r($params, true));
//         return [];
//     } catch (Throwable $th) {
//         error_log("General error in Optimized getMovies (v4) (Throwable): " . $th->getMessage());
//         error_log("Failed query was: " . $query . " Params: " . print_r($params, true));
//         return [];
//     }
//     error_log("Optimized getMovies (v4): Returning " . count($objects) . " objects.");
//     error_log("--- OPTIMIZED getMovies (v4) DEBUG END ---");
//     return $objects;
// }


function getMoviesCount($title) {
    $query = "SELECT COUNT(*) AS movie_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r ON r.tconst = t.tconst
              WHERE t.titleType = 'movie'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $search = "%" . $title . "%";
            $stmt->bindParam(':title', $search, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $row['movie_count'];
}

function getSeries($id)
{
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title,
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year,
                     endYear as end_year, runtimeMinutes as runtime_minutes, r.averageRating as rating,
                     numVotes as votes,
                     (
                         SELECT count(*)
                         FROM title_director_trim d
                         WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*)
                         FROM title_principals_trim p
                         WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*)
                         FROM title_writer_trim w
                         WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.titleType = 'tvSeries' AND t.tconst = :id";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $series = $stmt->fetchObject(Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $series;
}

function getAllSeries($offset, $limit, $title)
{
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title,
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year,
                     endYear as end_year, runtimeMinutes as runtime_minutes, t.genres,
                     r.averageRating as rating, r.numVotes as votes,
                     (
                         SELECT count(*)
                         FROM title_director_trim d
                         WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*)
                         FROM title_principals_trim p
                         WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*)
                         FROM title_writer_trim w
                         WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE titleType = 'tvSeries'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    $query .= " LIMIT :limit OFFSET :offset";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $title = "%" . $title . "%";
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $seriesList = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $seriesList;
}

function getSeriesCount($title)
{
    $query = "SELECT count(*) AS title_count
              FROM title_basics_trim AS t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE titleType = 'tvSeries'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $title = "%" . $title . "%";
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $row["title_count"];
}

function getShort($id)
{
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title,
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year,
                     endYear as end_year, runtimeMinutes as runtime_minutes, r.averageRating as rating,
                     numVotes as votes,
                     (
                         SELECT count(*)
                         FROM title_director_trim d
                         WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*)
                         FROM title_principals_trim p
                         WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*)
                         FROM title_writer_trim w
                         WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.titleType = 'short' AND t.tconst = :id";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $short = $stmt->fetchObject(Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $short;
}

function getShorts($offset, $limit, $title)
{
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title,
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year,
                     endYear as end_year, runtimeMinutes as runtime_minutes, t.genres,
                     r.averageRating as rating, r.numVotes as votes,
                     (
                         SELECT count(*)
                         FROM title_director_trim d
                         WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*)
                         FROM title_principals_trim p
                         WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*)
                         FROM title_writer_trim w
                         WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE titleType = 'short'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    $query .= " LIMIT :limit OFFSET :offset";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $title = "%" . $title . "%";
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $shorts = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $shorts;
}

function getShortsCount($title)
{
    $query = "SELECT count(*) AS title_count
              FROM title_basics_trim AS t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE titleType = 'short'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $title = "%" . $title . "%";
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $row["title_count"];
}


/**
 * Fetches a list of names (celebrities) with pagination and optional search.
 *
 * @param int $offset
 * @param int $limit
 * @param string $search_name
 * @return Name[]
 */
require_once __DIR__ . '/objects/Name.php'; // Or your correct path
require_once __DIR__ . '/PersonFetcher.php'; // Or your correct path

// ... (openConnection function) ...

function getNamesList($offset, $limit, $search_name = "") {
    // SQL query to fetch main data.
    // Column names/aliases here MUST match declared property names in the Name class
    // (can be public or protected for PDO::FETCH_CLASS).
    $query = "
        SELECT
            n.nconst,
            n.primaryName,
            CASE WHEN n.birthYear = '' OR n.birthYear = 0 THEN NULL ELSE n.birthYear END as birthYear,
            CASE WHEN n.deathYear = '' OR n.deathYear = 0 THEN NULL ELSE n.deathYear END as deathYear,
            n.primaryProfession, -- Raw pconsts string, Name class will resolve it
            n.image_url          -- Stored image_url from DB
        FROM
            name_basics_trim n
        WHERE 1 = 1
    ";

    $params = []; // Renamed from $paramsToBind for clarity in this context

    if (!empty($search_name)) {
        $query .= " AND n.primaryName LIKE :search_name ";
        $params[':search_name'] = "%" . $search_name . "%";
    }
    
    $query .= " ORDER BY n.primaryName ASC ";
    $query .= " LIMIT :limit OFFSET :offset";

    // Add limit and offset to params *after* other params are set,
    // as they are used directly in bindParam later.
    // No, wait, the original loop for binding $params is fine. Let's keep that structure.
    // The $params array will be built up and then iterated.

    $finalParams = []; // For clarity, distinct from the SQL $params array that will be created by PDO
     if (!empty($search_name)) {
        $finalParams[':search_name'] = "%" . $search_name . "%";
    }
    $finalParams[':limit'] = $limit;
    $finalParams[':offset'] = $offset;


    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        // Bind parameters
        if (!empty($search_name)) {
            $stmt->bindParam(':search_name', $finalParams[':search_name'], PDO::PARAM_STR);
        }
        $stmt->bindParam(':limit', $finalParams[':limit'], PDO::PARAM_INT);
        $stmt->bindParam(':offset', $finalParams[':offset'], PDO::PARAM_INT);

        $stmt->execute();
        
        // Fetch all rows directly as Name objects.
        // PDO will map columns to properties.
        // `PDO::FETCH_PROPS_LATE` calls constructor first, then sets properties.
        $nameObjects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Name::class);
        
        // $nameObjects will be an array of Name instances, or an empty array if no results.
        // Properties like nconst, primaryName, birthYear, deathYear, primaryProfession, image_url
        // should now be populated on each $nameObj IF they are declared in the Name class
        // (public or protected) and their names match the SQL column names/aliases.

        if ($nameObjects) {
            foreach ($nameObjects as $nameObj) {
                // Now, call methods on the already populated Name object
                // to resolve/fetch additional complex data.
                
                // Resolve professions (pconsts string to array of names)
                // This method uses $nameObj->primaryProfession which was set by PDO::FETCH_CLASS
                $nameObj->resolveProfessions($db); // Pass the DB connection
                
                // Fetch associated titles
                // This method uses $nameObj->nconst which was set by PDO::FETCH_CLASS
                $nameObj->fetchAssociatedTitles($db, 3); // Pass DB and limit for titles

                // The image URL ($nameObj->imageUrl) was populated by PDO::FETCH_CLASS
                // from the n.image_url column.
                // The logic to scrape if it's null is inside $nameObj->getImageUrl(),
                // which is called by $nameObj->jsonSerialize() if imageUrl is still null.
                // No explicit call to $nameObj->getImageUrl() is needed here unless you want to
                // force scraping *before* json_encode is called later by the API.
            }
        }
        
        return $nameObjects ?: []; // Return the array of (potentially enriched) Name objects or empty array

    } catch (PDOException $e) {
        error_log("Error in getNamesList (FETCH_CLASS version): " . $e->getMessage() . " Query: " . $query . " Params: " . print_r($finalParams, true));
        return []; // Return empty array on error
    }
}

// getNamesCount function (ensure it aligns with any new mandatory filters in getNamesList)
function getNamesCount($search_name = "") {
    $query = "SELECT count(*) AS name_count
              FROM name_basics_trim n
              WHERE 1 = 1 ";
    // Note: if getNamesList adds mandatory filters (e.g., birthYear IS NOT NULL),
    // getNamesCount must also include them for an accurate total.

    if (!empty($search_name)) {
        $query .= "AND n.primaryName LIKE :search_name ";
    }

    // Example: if birthYear is required
    // if (isset($_GET['requireBirthYear']) && $_GET['requireBirthYear'] == '1') {
    //     $query .= "AND n.birthYear IS NOT NULL ";
    // }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($search_name)) {
            $searchTerm = "%" . $search_name . "%";
            $stmt->bindParam(':search_name', $searchTerm, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row["name_count"] : 0;
    } catch (PDOException $e) {
        error_log("Error in getNamesCount: " . $e->getMessage());
        return 0;
    }
}
/**
 * Gets the count of names (celebrities) based on search term.
 *
 * @param string $search_name
 * @return int
 */
// function getNamesCount($search_name = "")
// {
//     $query = "SELECT count(*) AS name_count
//               FROM name_basics_trim n
//               WHERE 1 = 1 ";
//     $params = [];

//     if (!empty($search_name)) {
//         $query .= "AND n.primaryName LIKE :search_name ";
//         $params[':search_name'] = "%" . $search_name . "%";
//     }

//     try {
//         $db = openConnection();
//         $stmt = $db->prepare($query);
//         foreach ($params as $key => &$val) {
//             $stmt->bindParam($key, $val, PDO::PARAM_STR);
//         }
//         unset($val);

//         $stmt->execute();
//         $row = $stmt->fetch(PDO::FETCH_ASSOC);
//     } catch (PDOException $e) {
//         error_log("Error in getNamesCount: " . $e->getMessage());
//         return 0;
//     }
//     return $row ? (int)$row["name_count"] : 0;
// }

/**
 * Fetches a single name (celebrity) by their nconst ID.
 *
 * @param string $nconst
 * @return Name|null
 */
function getNameById($nconst) {
    $query = "SELECT n.nconst, n.primaryName, n.birthYear, n.deathYear,
                     n.primaryProfession, n.knownForTitles, n.image_url /* if available */
                     /* You might want to JOIN other tables for more detailed info like biography */
              FROM name_basics_trim n
              WHERE n.nconst = :nconst";
    $params = [':nconst' => $nconst];

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nconst', $nconst, PDO::PARAM_STR);
        $stmt->execute();
        $object = $stmt->fetchObject(Name::class); // Use Name class
    } catch (PDOException $e) {
        error_log("Error in getNameById: " . $e->getMessage());
        return null;
    }
    return $object ?: null;
}

// You might also want a function to get titles a person is known for,
// linking title_principals with title_basics.
function getTitlesForPerson($nconst, $limit = 5) {
    $query = "SELECT tb.tconst, tb.primaryTitle, tb.startYear, tp.category, tp.job, tp.characters
              FROM title_principals_trim tp
              JOIN title_basics_trim tb ON tp.tconst = tb.tconst
              WHERE tp.nconst = :nconst
              ORDER BY tb.startYear DESC, tb.primaryTitle ASC
              LIMIT :limit";
    // ... (execute query and return results) ...
}

?>