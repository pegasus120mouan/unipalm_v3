<?php

function getVerificateurs(PDO $conn): array
{
    $stmt = $conn->prepare(
        "SELECT * FROM utilisateurs WHERE role = 'verificateur' ORDER BY nom, prenoms"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVerificateurById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        "SELECT * FROM utilisateurs WHERE id = ? AND role = 'verificateur'"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function searchVerificateurs(PDO $conn, string $term): array
{
    $term = '%' . trim($term) . '%';
    $stmt = $conn->prepare(
        "SELECT * FROM utilisateurs
         WHERE role = 'verificateur'
           AND (nom LIKE ? OR prenoms LIKE ? OR login LIKE ? OR contact LIKE ?)
         ORDER BY nom, prenoms"
    );
    $stmt->execute([$term, $term, $term, $term]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
