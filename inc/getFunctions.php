<?php

function getTotalRecords($conn, $tableName, $Index) {
    if (!tableExists($conn, $tableName)) {
        return 0;
    }
    
    $query = "SELECT COUNT($Index) AS count FROM $tableName";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['count']);
    } else {
        return 0;
    }
}

function getFilteredRecordsCount($conn, $tableName, $whereClause, $Index) {
    if (!tableExists($conn, $tableName)) {
        return 0;
    }
    
    $query = "SELECT COUNT($Index) AS count FROM $tableName WHERE $whereClause";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['count']);
    } else {
        return 0;
    }
}

function tableExists($conn, $tableName) {
    $query = "SHOW TABLES LIKE '$tableName'";
    $result = $conn->query($query);

    return $result && $result->num_rows > 0;
}



?>


