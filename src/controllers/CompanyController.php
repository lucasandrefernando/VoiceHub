<?php

class CompanyController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Exibe a página de gerenciamento de empresas
     */
    public function index()
    {
        try {
            // Busca todas as empresas do banco de dados
            $companiesQuery = $this->db->query("SELECT * FROM companies ORDER BY name");
            $companies = $companiesQuery->fetchAll(PDO::FETCH_ASSOC);

            // Carrega a view, passando as empresas como variável
            require_once BASE_PATH . '/src/views/companies/company.php';
        } catch (PDOException $e) {
            error_log("Erro ao buscar empresas: " . $e->getMessage());
            echo "Ocorreu um erro ao carregar as empresas. Por favor, tente novamente mais tarde.";
        }
    }

    /**
     * Busca dados de uma empresa pelo CNPJ usando a API ReceitaWS
     */
    public function searchByCNPJ()
    {
        // Obtém o CNPJ da requisição POST e remove caracteres não numéricos
        $cnpj = $_POST['cnpj'] ?? '';
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Valida se o CNPJ tem 14 dígitos
        if (strlen($cnpj) != 14) {
            echo json_encode(['success' => false, 'message' => 'CNPJ inválido']);
            return;
        }

        // Tenta obter os dados da empresa
        $companyData = $this->getCompanyDataFromAPI($cnpj);

        if ($companyData) {
            echo json_encode(['success' => true, 'data' => $companyData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter os dados da empresa']);
        }
    }

    /**
     * Obtém dados da empresa da API ReceitaWS
     * @param string $cnpj CNPJ da empresa
     * @return array|null Dados da empresa ou null se não encontrado
     */
    private function getCompanyDataFromAPI($cnpj)
    {
        $url = "https://www.receitaws.com.br/v1/cnpj/{$cnpj}";

        // Configuração do contexto da requisição
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);

        // Faz a requisição à API
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("Erro ao acessar a API ReceitaWS");
            return null;
        }

        $data = json_decode($response, true);

        // Verifica se a API retornou um erro
        if (isset($data['status']) && $data['status'] == 'ERROR') {
            error_log("Erro retornado pela API ReceitaWS: " . ($data['message'] ?? 'Mensagem de erro não disponível'));
            return null;
        }

        // Retorna os dados formatados
        return [
            'name' => $data['nome'] ?? '',
            'cnpj' => $data['cnpj'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['logradouro'] ?? '',
            'number' => $data['numero'] ?? '',
            'complement' => $data['complemento'] ?? '',
            'neighborhood' => $data['bairro'] ?? '',
            'city' => $data['municipio'] ?? '',
            'state' => $data['uf'] ?? '',
            'zip_code' => $data['cep'] ?? '',
            'phone' => $data['telefone'] ?? '',
        ];
    }

    /**
     * Cria uma nova empresa
     */
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validação básica
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'Nome da empresa é obrigatório']);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO companies (name, cnpj, email, address, city, state, zip_code, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['cnpj'] ?? null,
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['phone'] ?? null
            ]);

            $newId = $this->db->lastInsertId();

            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Empresa criada com sucesso']);
        } catch (PDOException $e) {
            error_log("Erro ao criar empresa: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao criar empresa. Por favor, tente novamente.']);
        }
    }

    /**
     * Atualiza uma empresa existente
     * @param int $id ID da empresa a ser atualizada
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'Nome da empresa é obrigatório']);
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE companies SET name = ?, cnpj = ?, email = ?, address = ?, city = ?, state = ?, zip_code = ?, phone = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['cnpj'] ?? null,
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['phone'] ?? null,
                $id
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Empresa atualizada com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhuma alteração realizada ou empresa não encontrada']);
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar empresa: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar empresa. Por favor, tente novamente.']);
        }
    }

    /**
     * Exclui uma empresa
     * @param int $id ID da empresa a ser excluída
     */
    public function delete($id)
    {
        header('Content-Type: application/json');
        try {
            $this->db->beginTransaction();

            // Primeiro, exclua as licenças associadas
            $deleteLicensesStmt = $this->db->prepare("DELETE FROM licenses WHERE company_id = ?");
            $deleteLicensesStmt->execute([$id]);

            // Agora exclua a empresa
            $deleteCompanyStmt = $this->db->prepare("DELETE FROM companies WHERE id = ?");
            $deleteCompanyStmt->execute([$id]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Empresa e licenças associadas excluídas com sucesso']);
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao excluir empresa: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir empresa: ' . $e->getMessage()]);
        }
    }
}
