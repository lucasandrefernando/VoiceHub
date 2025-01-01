<?php

class RecordingController
{
    private $transkriptorApiKey;

    public function __construct()
    {
        // Inicializa a chave da API do Transkriptor a partir das variáveis de ambiente
        $this->transkriptorApiKey = $_ENV['TRANSKRIPTOR_API_KEY'];
    }

    /**
     * Exibe a lista de todas as gravações
     */
    public function index()
    {
        // Obtém todas as gravações processadas
        $recordings = $this->getProcessedRecordings();

        // Carrega a view com a lista de gravações
        require_once BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . 'recordings.php';
    }

    /**
     * Exibe os detalhes de uma gravação específica
     * @param string $recordingId ID da gravação
     */
    public function view($recordingId)
    {
        // Obtém os detalhes da gravação específica
        $recording = $this->getRecording($recordingId);

        if (!$recording) {
            // Se a gravação não for encontrada, exibe a página 404
            header("HTTP/1.0 404 Not Found");
            require_once BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . '404.php';
            return;
        }

        // Carrega a view com os detalhes da gravação
        require_once BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . 'view.php';
    }

    /**
     * Obtém todas as gravações processadas
     * @return array Lista de gravações processadas
     */
    private function getProcessedRecordings()
    {
        $recordings = [];
        $files = glob(BASE_PATH . DIRECTORY_SEPARATOR . 'gravacoes' . DIRECTORY_SEPARATOR . '*.gsm');

        foreach ($files as $file) {
            $filename = basename($file);
            $wavFile = $this->getWavFilePath($filename);
            $transcriptionFile = $this->getTranscriptionFilePath($filename);

            // Converte o arquivo para WAV se ainda não foi convertido
            if (!file_exists($wavFile)) {
                $this->convertGsmToWav($filename);
            }

            // Transcreve o áudio se ainda não foi transcrito ou se a transcrição está pendente há mais de 10 minutos
            if (!file_exists($transcriptionFile) || (file_exists($transcriptionFile) && time() - filemtime($transcriptionFile) > 600)) {
                $this->transcribeAudio($filename);
            }

            // Adiciona a gravação à lista
            $recordings[] = [
                'id' => md5($filename),
                'filename' => $filename,
                'wavFile' => '/voicehub/public/gravacoes/' . pathinfo($filename, PATHINFO_FILENAME) . '.wav',
                'transcription' => file_exists($transcriptionFile) ? file_get_contents($transcriptionFile) : 'Transcrição em andamento...'
            ];
        }
        return $recordings;
    }

    /**
     * Obtém os detalhes de uma gravação específica
     * @param string $recordingId ID da gravação
     * @return array|null Detalhes da gravação ou null se não encontrada
     */
    private function getRecording($recordingId)
    {
        $files = glob(BASE_PATH . DIRECTORY_SEPARATOR . 'gravacoes' . DIRECTORY_SEPARATOR . '*.gsm');
        foreach ($files as $file) {
            $filename = basename($file);
            if (md5($filename) === $recordingId) {
                $wavFile = $this->getWavFilePath($filename);
                $transcriptionFile = $this->getTranscriptionFilePath($filename);

                // Converte o arquivo para WAV se ainda não foi convertido
                if (!file_exists($wavFile)) {
                    $this->convertGsmToWav($filename);
                }

                // Transcreve o áudio se ainda não foi transcrito
                if (!file_exists($transcriptionFile)) {
                    $this->transcribeAudio($filename);
                }

                return [
                    'id' => $recordingId,
                    'filename' => $filename,
                    'wavFile' => $wavFile,
                    'transcription' => file_exists($transcriptionFile) ? file_get_contents($transcriptionFile) : 'Transcrição em andamento...'
                ];
            }
        }
        return null;
    }

    /**
     * Converte um arquivo GSM para WAV
     * @param string $filename Nome do arquivo GSM
     * @return bool Verdadeiro se a conversão foi bem-sucedida, falso caso contrário
     */
    private function convertGsmToWav($filename)
    {
        $gsmFile = BASE_PATH . DIRECTORY_SEPARATOR . "gravacoes" . DIRECTORY_SEPARATOR . $filename;
        $wavFile = $this->getWavFilePath($filename);
        $command = "ffmpeg -i \"$gsmFile\" -acodec pcm_s16le -ar 16000 \"$wavFile\"";
        exec($command, $output, $returnVar);
        error_log("Convertendo $filename para WAV. Comando: $command, Resultado: " . implode(", ", $output) . ", ReturnVar: $returnVar");
        return $returnVar === 0;
    }

    /**
     * Transcreve um arquivo de áudio
     * @param string $filename Nome do arquivo de áudio
     * @return bool Verdadeiro se a transcrição foi bem-sucedida, falso caso contrário
     */
    private function transcribeAudio($filename)
    {
        $wavFile = $this->getWavFilePath($filename);
        $transcriptionFile = $this->getTranscriptionFilePath($filename);

        error_log("Iniciando transcrição para $filename");

        $transcriptionId = $this->startTranscription($wavFile);
        if ($transcriptionId) {
            error_log("Transcrição iniciada com ID: $transcriptionId");
            $transcript = $this->getTranscriptionResult($transcriptionId);
            if ($transcript) {
                error_log("Transcrição concluída para $filename");
                file_put_contents($transcriptionFile, $transcript);
                return true;
            } else {
                error_log("Falha ao obter resultado da transcrição para $filename");
            }
        } else {
            error_log("Falha ao iniciar transcrição para $filename");
        }
        return false;
    }


    /**
     * Obtém o caminho do arquivo WAV correspondente a um arquivo GSM
     * @param string $filename Nome do arquivo GSM
     * @return string Caminho do arquivo WAV
     */
    private function getWavFilePath($filename)
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . "gravacoes" . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . ".wav";
    }

    /**
     * Obtém o caminho do arquivo de transcrição correspondente a um arquivo de áudio
     * @param string $filename Nome do arquivo de áudio
     * @return string Caminho do arquivo de transcrição
     */
    private function getTranscriptionFilePath($filename)
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . "gravacoes" . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . ".txt";
    }

    /**
     * Inicia o processo de transcrição com a API do Transkriptor
     * @param string $audioFile Caminho do arquivo de áudio
     * @return string|null ID da transcrição ou null em caso de erro
     */
    private function startTranscription($audioFile)
    {
        $ch = curl_init('https://transkriptor.com/api/v1/transcriptions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'audio' => new CURLFile($audioFile),
            'language' => 'pt-BR'
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->transkriptorApiKey,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("Resposta da API (startTranscription): " . $response);
        error_log("HTTP Code: " . $httpcode);

        if ($httpcode != 200) {
            return null;
        }

        $result = json_decode($response, true);
        return $result['id'] ?? null;
    }

    /**
     * Obtém o resultado da transcrição da API do Transkriptor
     * @param string $transcriptionId ID da transcrição
     * @return string|null Texto transcrito ou null em caso de erro
     */
    private function getTranscriptionResult($transcriptionId)
    {
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $ch = curl_init("https://transkriptor.com/api/v1/transcriptions/{$transcriptionId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->transkriptorApiKey,
            ]);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log("Resposta da API (getTranscriptionResult): " . $response);
            error_log("HTTP Code: " . $httpcode);

            if ($httpcode != 200) {
                return null;
            }

            $result = json_decode($response, true);
            if ($result['status'] === 'completed') {
                return $result['text'];
            } elseif ($result['status'] === 'failed') {
                error_log("Transcrição falhou para ID: $transcriptionId");
                return null;
            }

            $attempt++;
            sleep(10);
        }

        error_log("Tempo limite excedido para transcrição ID: $transcriptionId");
        return null;
    }
}
