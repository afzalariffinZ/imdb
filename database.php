<?php

require_once 'connection.php';
require_once './objects/ArrayValue.php';
require_once './objects/Title.php';
require_once __DIR__ . '/PosterFetcher.php'; // Or its correct path
require_once __DIR__ . '/PersonFetcher.php'; // Or its correct path
/**
 * Create connection to the database
 *
 * @return PDO (PHP Data Objects) provides access to the database
 */
function openConnection(): PDO
{

    try {
        // Use the original DSN for connection as defined in connection.php
        $pdo = new PDO(
            CONNECTION_STRING,
            CONNECTION_USER,     // Usually null for SQLite
            CONNECTION_PASSWORD, // Usually null for SQLite
            CONNECTION_OPTIONS   // Should include PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ); // Wait up to 5000ms (5 seconds)
        

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


function getAll(
    $offset,
    $limit,
    $title_str = null,
    $minRating = null,
    $maxRating = null,
    $genres_str = null,
    $startYearFrom = null, // For title's startYear
    $startYearTo = null,   // For title's startYear
    $isAdult = null,       // '0' for no, '1' for yes
    $titleTypes_str = null // Comma-separated string of title types
) {
    $query = "SELECT t.tconst as id, 
                    t.image_url,
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
              JOIN title_ratings_trim r on r.tconst = t.tconst";

    $whereClauses = [];
    $paramsToBind = [];

    if (!empty($title_str)) {
        $whereClauses[] = "(t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
        $paramsToBind[':title_search'] = ["%" . $title_str . "%", PDO::PARAM_STR];
    }

    if ($minRating !== null && is_numeric($minRating)) {
        $whereClauses[] = "r.averageRating >= :minRating";
        $paramsToBind[':minRating'] = [(string)$minRating, PDO::PARAM_STR];
    }
    if ($maxRating !== null && is_numeric($maxRating)) {
        $whereClauses[] = "r.averageRating <= :maxRating";
        $paramsToBind[':maxRating'] = [(string)$maxRating, PDO::PARAM_STR];
    }
    if ($startYearFrom !== null && is_numeric($startYearFrom)) {
        $whereClauses[] = "t.startYear >= :startYearFrom";
        $paramsToBind[':startYearFrom'] = [(int)$startYearFrom, PDO::PARAM_INT];
    }
    if ($startYearTo !== null && is_numeric($startYearTo)) {
        $whereClauses[] = "t.startYear <= :startYearTo";
        $paramsToBind[':startYearTo'] = [(int)$startYearTo, PDO::PARAM_INT];
    }
    if ($isAdult !== null && ($isAdult === '0' || $isAdult === '1')) {
        $whereClauses[] = "t.isAdult = :isAdult";
        $paramsToBind[':isAdult'] = [(int)$isAdult, PDO::PARAM_INT];
    }

    if (!empty($genres_str)) {
        $genres_array = array_filter(array_map('trim', explode(',', $genres_str)));
        if (!empty($genres_array)) {
            $genreWhereClauses = [];
            foreach ($genres_array as $index => $genre) {
                $paramName = ':genre' . $index;
                $genreWhereClauses[] = "t.genres LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $genre . "%", PDO::PARAM_STR];
            }
            if (!empty($genreWhereClauses)) {
                $whereClauses[] = "(" . implode(" OR ", $genreWhereClauses) . ")"; // Match ANY selected genre
            }
        }
    }
    
    if (!empty($titleTypes_str)) {
        $titleTypes_array = array_filter(array_map('trim', explode(',', $titleTypes_str)));
        if(!empty($titleTypes_array)) {
            // Create a placeholder string like "(:type0, :type1, ...)"
            $typePlaceholders = implode(', ', array_map(function($i) { return ":type{$i}"; }, array_keys($titleTypes_array)));
            $whereClauses[] = "t.titleType IN (" . $typePlaceholders . ")";
            foreach ($titleTypes_array as $index => $ttype) {
                $paramsToBind[":type{$index}"] = [$ttype, PDO::PARAM_STR];
            }
        }
    }


    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $query .= " ORDER BY r.numVotes DESC, t.startYear DESC ";
    $query .= " LIMIT :limit OFFSET :offset";
    $paramsToBind[':limit'] = [(int)$limit, PDO::PARAM_INT];
    $paramsToBind[':offset'] = [(int)$offset, PDO::PARAM_INT];

    $objects = [];
    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        
        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }
        
        $stmt->execute();
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);

    } catch (PDOException $e) {
        error_log("Database error in getAll: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
    }
    return $objects;
}

function getAllCounts(
    $title_str = null,
    $minRating = null,
    $maxRating = null,
    $genres_str = null,
    $startYearFrom = null,
    $startYearTo = null,
    $isAdult = null,
    $titleTypes_str = null
) {
    $query = "SELECT COUNT(*) AS total_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r ON r.tconst = t.tconst";

    $whereClauses = [];
    $paramsToBind = [];

    if (!empty($title_str)) {
        $whereClauses[] = "(t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
        $paramsToBind[':title_search'] = ["%" . $title_str . "%", PDO::PARAM_STR];
    }
    
    // IMPORTANT: Notice `getAllCounts` was previously hardcoding `WHERE t.titleType = 'movie'`.
    // This has been removed to count ALL types, and now we'll filter by $titleTypes_str if provided.
    // If $titleTypes_str is NOT provided, it counts all types. If you want a default (like only 'movie' if nothing specified),
    // you'd add logic here or in api_all.php to set a default value for $titleTypes_str.

    if ($minRating !== null && is_numeric($minRating)) {
        $whereClauses[] = "r.averageRating >= :minRating";
        $paramsToBind[':minRating'] = [(string)$minRating, PDO::PARAM_STR];
    }
    if ($maxRating !== null && is_numeric($maxRating)) {
        $whereClauses[] = "r.averageRating <= :maxRating";
        $paramsToBind[':maxRating'] = [(string)$maxRating, PDO::PARAM_STR];
    }
    if ($startYearFrom !== null && is_numeric($startYearFrom)) {
        $whereClauses[] = "t.startYear >= :startYearFrom";
        $paramsToBind[':startYearFrom'] = [(int)$startYearFrom, PDO::PARAM_INT];
    }
    if ($startYearTo !== null && is_numeric($startYearTo)) {
        $whereClauses[] = "t.startYear <= :startYearTo";
        $paramsToBind[':startYearTo'] = [(int)$startYearTo, PDO::PARAM_INT];
    }
    if ($isAdult !== null && ($isAdult === '0' || $isAdult === '1')) {
        $whereClauses[] = "t.isAdult = :isAdult";
        $paramsToBind[':isAdult'] = [(int)$isAdult, PDO::PARAM_INT];
    }

    if (!empty($genres_str)) {
        $genres_array = array_filter(array_map('trim', explode(',', $genres_str)));
        if (!empty($genres_array)) {
            $genreWhereClauses = [];
            foreach ($genres_array as $index => $genre) {
                $paramName = ':genre' . $index;
                $genreWhereClauses[] = "t.genres LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $genre . "%", PDO::PARAM_STR];
            }
            if(!empty($genreWhereClauses)){
                $whereClauses[] = "(" . implode(" OR ", $genreWhereClauses) . ")";
            }
        }
    }
    
    if (!empty($titleTypes_str)) {
        $titleTypes_array = array_filter(array_map('trim', explode(',', $titleTypes_str)));
        if(!empty($titleTypes_array)) {
            $typePlaceholders = implode(', ', array_map(function($i) { return ":type{$i}"; }, array_keys($titleTypes_array)));
            $whereClauses[] = "t.titleType IN (" . $typePlaceholders . ")";
            foreach ($titleTypes_array as $index => $ttype) {
                $paramsToBind[":type{$index}"] = [$ttype, PDO::PARAM_STR];
            }
        }
    }

    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total_count'] : 0; // Changed alias to total_count
    } catch (PDOException $e) {
        error_log("Database error in getAllCounts: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
        // die($e->getMessage()); // Avoid die in API, return 0 or let exception bubble up
        return 0;
    }
}

function getTitles(
    $offset,
    $limit,
    $title_str,
    $type,
    $minRating = null,
    $maxRating = null,
    $genres_str = null, // Comma-separated string of genres
    $startYear = null,
    $endYear = null,
    $isAdult = null // '0' for no, '1' for yes, null/empty for any
) {
    $query = "SELECT t.tconst as id, 
                    t.image_url,
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
              JOIN title_ratings_trim r on r.tconst = t.tconst";

    $whereClauses = [];
    $paramsToBind = [];

    $whereClauses[] = "t.titleType = :type";
    $paramsToBind[':type'] = [$type, PDO::PARAM_STR];

    if (!empty($title_str)) {
        $whereClauses[] = "(t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
        $paramsToBind[':title_search'] = ["%" . $title_str . "%", PDO::PARAM_STR];
    }

    if ($minRating !== null && is_numeric($minRating)) {
        $whereClauses[] = "r.averageRating >= :minRating";
        // PDO does not have a PARAM_FLOAT. Bind as string, DB will cast.
        $paramsToBind[':minRating'] = [(string)$minRating, PDO::PARAM_STR];
    }
    if ($maxRating !== null && is_numeric($maxRating)) {
        $whereClauses[] = "r.averageRating <= :maxRating";
        $paramsToBind[':maxRating'] = [(string)$maxRating, PDO::PARAM_STR];
    }
    if ($startYear !== null && is_numeric($startYear)) {
        $whereClauses[] = "t.startYear >= :startYear";
        $paramsToBind[':startYear'] = [(int)$startYear, PDO::PARAM_INT];
    }
    if ($endYear !== null && is_numeric($endYear)) {
        // Assuming endYear is the upper bound for the movie's startYear
        $whereClauses[] = "t.startYear <= :endYear";
        $paramsToBind[':endYear'] = [(int)$endYear, PDO::PARAM_INT];
    }
    if ($isAdult !== null && ($isAdult === '0' || $isAdult === '1')) {
        $whereClauses[] = "t.isAdult = :isAdult";
        $paramsToBind[':isAdult'] = [(int)$isAdult, PDO::PARAM_INT];
    }

    if (!empty($genres_str)) {
        $genres_array = array_filter(array_map('trim', explode(',', $genres_str)));
        if (!empty($genres_array)) {
            $genreWhereClauses = [];
            foreach ($genres_array as $index => $genre) {
                $paramName = ':genre' . $index;
                // Assuming genres in DB are like "Action,Comedy,Drama"
                // This finds titles where the genre is a substring.
                $genreWhereClauses[] = "t.genres LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $genre . "%", PDO::PARAM_STR];
            }
            if (!empty($genreWhereClauses)) {
                 // All selected genres must be present if using AND
                 // Any of selected genres if using OR
                 // Assuming user wants movies that have ANY of the selected genres:
                $whereClauses[] = "(" . implode(" OR ", $genreWhereClauses) . ")";
            }
        }
    }

    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $query .= " ORDER BY r.numVotes DESC, t.startYear DESC ";
    $query .= " LIMIT :limit OFFSET :offset";
    $paramsToBind[':limit'] = [(int)$limit, PDO::PARAM_INT];
    $paramsToBind[':offset'] = [(int)$offset, PDO::PARAM_INT];

    $objects = [];
    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        
        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }
        
        $stmt->execute();
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);

    } catch (PDOException $e) {
        error_log("Database error in getTitles: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
    }
    return $objects;
}

function getTitleCount(
    $title_str,
    $type,
    $minRating = null,
    $maxRating = null,
    $genres_str = null,
    $startYear = null,
    $endYear = null,
    $isAdult = null
) {
    $query = "SELECT COUNT(*) AS movie_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r ON r.tconst = t.tconst";

    $whereClauses = [];
    $paramsToBind = [];

    $whereClauses[] = "t.titleType = :type";
    $paramsToBind[':type'] = [$type, PDO::PARAM_STR];

    if (!empty($title_str)) {
        $whereClauses[] = "(t.primaryTitle LIKE :title_search OR t.originalTitle LIKE :title_search)";
        $paramsToBind[':title_search'] = ["%" . $title_str . "%", PDO::PARAM_STR];
    }

    if ($minRating !== null && is_numeric($minRating)) {
        $whereClauses[] = "r.averageRating >= :minRating";
        $paramsToBind[':minRating'] = [(string)$minRating, PDO::PARAM_STR];
    }
    if ($maxRating !== null && is_numeric($maxRating)) {
        $whereClauses[] = "r.averageRating <= :maxRating";
        $paramsToBind[':maxRating'] = [(string)$maxRating, PDO::PARAM_STR];
    }
    if ($startYear !== null && is_numeric($startYear)) {
        $whereClauses[] = "t.startYear >= :startYear";
        $paramsToBind[':startYear'] = [(int)$startYear, PDO::PARAM_INT];
    }
    if ($endYear !== null && is_numeric($endYear)) {
        $whereClauses[] = "t.startYear <= :endYear";
        $paramsToBind[':endYear'] = [(int)$endYear, PDO::PARAM_INT];
    }
    if ($isAdult !== null && ($isAdult === '0' || $isAdult === '1')) {
        $whereClauses[] = "t.isAdult = :isAdult";
        $paramsToBind[':isAdult'] = [(int)$isAdult, PDO::PARAM_INT];
    }

    if (!empty($genres_str)) {
        $genres_array = array_filter(array_map('trim', explode(',', $genres_str)));
        if (!empty($genres_array)) {
            $genreWhereClauses = [];
            foreach ($genres_array as $index => $genre) {
                $paramName = ':genre' . $index;
                $genreWhereClauses[] = "t.genres LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $genre . "%", PDO::PARAM_STR];
            }
            if(!empty($genreWhereClauses)){
                $whereClauses[] = "(" . implode(" OR ", $genreWhereClauses) . ")";
            }
        }
    }

    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['movie_count'] : 0;
    } catch (PDOException $e) {
        error_log("Database error in getTitleCount: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
        return 0; 
    }
}


function getNamesCount(
    $search_name = "",
    $professions_str = null, // Comma-separated string of professions
    $birthYearStart = null,
    $birthYearEnd = null
) {
    $query = "SELECT count(*) AS name_count
              FROM name_basics_trim n
              WHERE 1 = 1 ";

    $paramsToBind = [];

    if (!empty($search_name)) {
        $query .= "AND n.primaryName LIKE :search_name ";
        $paramsToBind[':search_name'] = ["%" . $search_name . "%", PDO::PARAM_STR];
    }

    if ($birthYearStart !== null && is_numeric($birthYearStart)) {
        // Ensure birthYear is not empty or 0 before comparing, or handle NULLs appropriately
        $query .= "AND (n.birthYear IS NOT NULL AND n.birthYear != '' AND n.birthYear != 0 AND n.birthYear >= :birthYearStart) ";
        $paramsToBind[':birthYearStart'] = [(int)$birthYearStart, PDO::PARAM_INT];
    }
    if ($birthYearEnd !== null && is_numeric($birthYearEnd)) {
        $query .= "AND (n.birthYear IS NOT NULL AND n.birthYear != '' AND n.birthYear != 0 AND n.birthYear <= :birthYearEnd) ";
        $paramsToBind[':birthYearEnd'] = [(int)$birthYearEnd, PDO::PARAM_INT];
    }

    if (!empty($professions_str)) {
        $professions_array = array_filter(array_map('trim', explode(',', $professions_str)));
        if (!empty($professions_array)) {
            $professionWhereClauses = [];
            foreach ($professions_array as $index => $profession) {
                $paramName = ':profession' . $index;
                // Assuming professions are stored like "actor,director,producer"
                $professionWhereClauses[] = "n.primaryProfession LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $profession . "%", PDO::PARAM_STR];
            }
            if (!empty($professionWhereClauses)) {
                // Assuming user wants people who have ANY of the selected professions
                $query .= "AND (" . implode(" OR ", $professionWhereClauses) . ") ";
            }
        }
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row["name_count"] : 0;
    } catch (PDOException $e) {
        error_log("Error in getNamesCount: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
        return 0;
    }
}

function getNamesList(
    $offset,
    $limit,
    $search_name = "",
    $professions_str = null,
    $birthYearStart = null,
    $birthYearEnd = null
) {
    $query = "
        SELECT
            n.nconst,
            n.primaryName,
            CASE WHEN n.birthYear = '' OR n.birthYear = 0 THEN NULL ELSE n.birthYear END as birthYear,
            CASE WHEN n.deathYear = '' OR n.deathYear = 0 THEN NULL ELSE n.deathYear END as deathYear,
            n.primaryProfession,
            n.image_url
        FROM
            name_basics_trim n
        WHERE 1 = 1
    ";

    $paramsToBind = [];

    if (!empty($search_name)) {
        $query .= " AND n.primaryName LIKE :search_name ";
        $paramsToBind[':search_name'] = ["%" . $search_name . "%", PDO::PARAM_STR];
    }

    if ($birthYearStart !== null && is_numeric($birthYearStart)) {
        $query .= "AND (n.birthYear IS NOT NULL AND n.birthYear != '' AND n.birthYear != 0 AND n.birthYear >= :birthYearStart) ";
        $paramsToBind[':birthYearStart'] = [(int)$birthYearStart, PDO::PARAM_INT];
    }
    if ($birthYearEnd !== null && is_numeric($birthYearEnd)) {
        $query .= "AND (n.birthYear IS NOT NULL AND n.birthYear != '' AND n.birthYear != 0 AND n.birthYear <= :birthYearEnd) ";
        $paramsToBind[':birthYearEnd'] = [(int)$birthYearEnd, PDO::PARAM_INT];
    }
    
    if (!empty($professions_str)) {
        $professions_array = array_filter(array_map('trim', explode(',', $professions_str)));
        if (!empty($professions_array)) {
            $professionWhereClauses = [];
            foreach ($professions_array as $index => $profession) {
                $paramName = ':profession' . $index;
                $professionWhereClauses[] = "n.primaryProfession LIKE " . $paramName;
                $paramsToBind[$paramName] = ["%" . $profession . "%", PDO::PARAM_STR];
            }
            if(!empty($professionWhereClauses)){
                $query .= "AND (" . implode(" OR ", $professionWhereClauses) . ") ";
            }
        }
    }
    
    $query .= " ORDER BY n.primaryName ASC "; // Could also order by knownForTitlesCount if that becomes a field
    $query .= " LIMIT :limit OFFSET :offset";
    $paramsToBind[':limit'] = [(int)$limit, PDO::PARAM_INT];
    $paramsToBind[':offset'] = [(int)$offset, PDO::PARAM_INT];


    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        foreach ($paramsToBind as $param => list($value, $type)) {
            $stmt->bindValue($param, $value, $type);
        }

        $stmt->execute();
        
        $nameObjects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Name::class);

        if ($nameObjects) {
            foreach ($nameObjects as $nameObj) {
                // These could be conditionally called if performance is an issue for list views
                // For now, keeping them for consistency with your original code.
                $nameObj->resolveProfessions($db); 
                $nameObj->fetchAssociatedTitles($db, 3); 
            }
        }
        
        return $nameObjects ?: [];

    } catch (PDOException $e) {
        error_log("Error in getNamesList: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r(array_map(function($v) { return $v[0]; }, $paramsToBind), true));
        return [];
    }
}

?>