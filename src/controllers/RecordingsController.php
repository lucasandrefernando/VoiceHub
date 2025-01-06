<?php
// src/controllers/RecordingsController.php

class RecordingsController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index()
    {
        $recordings = $this->db->query("SELECT * FROM records ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/src/views/recordings/recordings.php';
    }

    public function getRecordingDetails($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM records WHERE id = ?");
        $stmt->execute([$id]);
        $recording = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recording) {
            return ['error' => 'Gravação não encontrada'];
        }

        $transcription = file_get_contents($recording['transcricao_local']);

        return [
            'id' => $recording['id'],
            'audio_file_uri' => $recording['audio_file_uri'],
            'transcription' => $transcription
        ];
    }

    public function analyze()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => 'Método não permitido'];
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            return ['error' => 'ID da gravação não fornecido'];
        }

        $stmt = $this->db->prepare("SELECT * FROM records WHERE id = ?");
        $stmt->execute([$id]);
        $recording = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recording) {
            return ['error' => 'Gravação não encontrada'];
        }

        $transcription = file_get_contents($recording['transcricao_local']);
        $analysis = $this->getAIAnalysis($transcription);

        return ['analysis' => $analysis];
    }

    private function getAIAnalysis($transcription)
    {
        $wordCount = str_word_count($transcription);
        $sentenceCount = preg_match_all('/[.!?]+/', $transcription, $matches);

        $analysis = "Análise da transcrição:\n\n";
        $analysis .= "- Número de palavras: $wordCount\n";
        $analysis .= "- Número de frases: $sentenceCount\n";

        if ($wordCount < 50) {
            $analysis .= "\nA transcrição é relativamente curta.";
        } elseif ($wordCount > 200) {
            $analysis .= "\nA transcrição é relativamente longa.";
        }

        return ['analysis' => $analysis];
    }
}
