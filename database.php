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
    try {
        $pdo = new PDO(
            CONNECTION_STRING,
            CONNECTION_USER,
            CONNECTION_PASSWORD,
            CONNECTION_OPTIONS
        );
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }

    return $pdo;
}

function getTitles($offset, $limit, $title /* Define more parameters for filtering, e.g. rating, date, etc. */ )
{
    // WARNING! This is a slow query because it contains subqueries.
    // It would be better implemented a separate queries specific to any given (filtering, pagination) purpose.
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
              WHERE 1 = 1 "; // This allows us to tack on filtering and sorting and limiting clauses later on.

    if (!empty($title)) {
        $query .= "AND (primaryTitle LIKE :title or originalTitle LIKE :title) ";
    }

    $query .= "LIMIT :limit OFFSET :offset";

    try {
        $imdb = openConnection();
        $stmt = $imdb->prepare($query);

        if (!empty($title)) {
            $title = "%" . $title . "%";
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        }

        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    return $objects;
}

function getTitleCount($title)
{
    $query = "SELECT count(*) AS title_count
              FROM title_basics_trim AS t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE 1 = 1 ";

    if (!empty($title)) {
        $query = $query . "AND (primaryTitle LIKE :title or originalTitle LIKE :title) ";
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

function getMovie($id) {
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
              WHERE t.tconst = :id AND t.titleType = 'movie'";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $object = $stmt->fetchObject(Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    return $object;
}

function getMovies($offset, $limit, $title) {
    $query = "SELECT t.tconst as id, titleType as title_type, primaryTitle as primary_title, 
                     originalTitle as original_title, isAdult as is_adult, startYear as start_year, 
                     endYear as end_year, runtimeMinutes as runtime_minutes, t.genres, 
                     r.averageRating as rating, r.numVotes as votes,
                     (
                         SELECT count(*) FROM title_director_trim d WHERE d.tconst = t.tconst
                     ) as directors_count,
                     (
                         SELECT count(*) FROM title_principals_trim p WHERE p.tconst = t.tconst
                     ) as principals_count,
                     (
                         SELECT count(*) FROM title_writer_trim w WHERE w.tconst = t.tconst
                     ) as writers_count
              FROM title_basics_trim t
              JOIN title_ratings_trim r on r.tconst = t.tconst
              WHERE t.titleType = 'movie'";

    if (!empty($title)) {
        $query .= " AND (primaryTitle LIKE :title OR originalTitle LIKE :title)";
    }

    $query .= " LIMIT :limit OFFSET :offset";

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);

        if (!empty($title)) {
            $search = "%" . $title . "%";
            $stmt->bindParam(':title', $search, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Title::class);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

    return $objects;
}

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


require_once './Name.php'; // Create this Name class

/**
 * Fetches a list of names (celebrities) with pagination and optional search.
 *
 * @param int $offset
 * @param int $limit
 * @param string $search_name
 * @return Name[]
 */
function getNamesList($offset, $limit, $search_name = "")
{
    $query = "SELECT n.nconst, n.primaryName, n.birthYear, n.deathYear,
                     n.primaryProfession, n.knownForTitles, n.image_url /* if you have this */
              FROM name_basics_trim n
              WHERE 1 = 1 ";

    $params = [];

    if (!empty($search_name)) {
        $query .= "AND n.primaryName LIKE :search_name ";
        $params[':search_name'] = "%" . $search_name . "%";
    }

    // Add ordering, e.g., by name or by some popularity metric if you have one
    $query .= "ORDER BY n.primaryName ASC ";
    $query .= "LIMIT :limit OFFSET :offset";

    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        foreach ($params as $key => &$val) {
            $param_type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($key, $val, $param_type);
        }
        unset($val);

        $stmt->execute();
        $objects = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Name::class); // Use Name class
    } catch (PDOException $e) {
        error_log("Error in getNamesList: " . $e->getMessage());
        return [];
    }
    return $objects ?: [];
}

/**
 * Gets the count of names (celebrities) based on search term.
 *
 * @param string $search_name
 * @return int
 */
function getNamesCount($search_name = "")
{
    $query = "SELECT count(*) AS name_count
              FROM name_basics_trim n
              WHERE 1 = 1 ";
    $params = [];

    if (!empty($search_name)) {
        $query .= "AND n.primaryName LIKE :search_name ";
        $params[':search_name'] = "%" . $search_name . "%";
    }

    try {
        $db = openConnection();
        $stmt = $db->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val, PDO::PARAM_STR);
        }
        unset($val);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getNamesCount: " . $e->getMessage());
        return 0;
    }
    return $row ? (int)$row["name_count"] : 0;
}

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