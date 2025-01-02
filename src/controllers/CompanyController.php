<?php

/**
 * CompanyController
 * 
 * Esta classe é responsável por gerenciar as operações relacionadas às empresas,
 * incluindo listagem, criação e armazenamento de dados de empresas.
 */
class CompanyController
{
    /** @var PDO $db Conexão com o banco de dados */
    private $db;

    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Exibe a lista de todas as empresas
     */
    public function index()
    {
        try {
            $companies = $this->getAllCompanies();
            require_once BASE_PATH . '/src/views/companies/index.php';
        } catch (PDOException $e) {
            error_log("Erro ao buscar empresas: " . $e->getMessage());
            $_SESSION['error_message'] = "Ocorreu um erro ao carregar a lista de empresas. Por favor, tente novamente mais tarde.";
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        }
    }

    /**
     * Exibe o formulário para criar uma nova empresa
     */
    public function create()
    {
        require_once BASE_PATH . '/src/views/companies/create.php';
    }

    /**
     * Processa o formulário de criação de empresa
     * Busca os dados da empresa na API e salva no banco de dados
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);

            try {
                $companyData = $this->getCompanyDataFromAPI($cnpj);

                if ($companyData) {
                    $result = $this->saveCompany($companyData);
                    if ($result) {
                        $_SESSION['success_message'] = "Empresa cadastrada com sucesso!";
                    } else {
                        $_SESSION['error_message'] = "Erro ao cadastrar empresa. Por favor, tente novamente.";
                    }
                } else {
                    $_SESSION['error_message'] = "Não foi possível obter os dados da empresa. Verifique o CNPJ e tente novamente.";
                }
            } catch (Exception $e) {
                error_log("Erro ao cadastrar empresa: " . $e->getMessage());
                $_SESSION['error_message'] = "Ocorreu um erro ao cadastrar a empresa. Por favor, tente novamente mais tarde.";
            }
        }
        header("Location: " . BASE_URL . "/companies");
        exit();
    }

    /**
     * Busca todas as empresas do banco de dados
     * 
     * @return array Lista de empresas
     * @throws PDOException Se ocorrer um erro na consulta ao banco de dados
     */
    private function getAllCompanies()
    {
        $stmt = $this->db->query("SELECT * FROM companies ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca os dados da empresa na API da ReceitaWS
     * 
     * @param string $cnpj CNPJ da empresa
     * @return array|null Dados da empresa ou null se não encontrada
     * @throws Exception Se ocorrer um erro na requisição à API
     */
    private function getCompanyDataFromAPI($cnpj)
    {
        $url = "https://www.receitaws.com.br/v1/cnpj/" . $cnpj;
        $response = @file_get_contents($url);

        if ($response === false) {
            throw new Exception("Falha na requisição à API da ReceitaWS");
        }

        $data = json_decode($response, true);

        if ($data['status'] === 'OK') {
            return [
                'cnpj' => $data['cnpj'],
                'name' => $data['nome'],
                'trade_name' => $data['fantasia'],
                'address' => $data['logradouro'] . ', ' . $data['numero'] . ' - ' . $data['complemento'],
                'city' => $data['municipio'],
                'state' => $data['uf'],
                'zip_code' => $data['cep'],
                'phone' => $data['telefone'],
                'email' => $data['email'],
                'website' => isset($data['website']) ? $data['website'] : null,
            ];
        }
        return null;
    }

    /**
     * Salva os dados da empresa no banco de dados
     * 
     * @param array $data Dados da empresa
     * @return bool True se salvou com sucesso, False caso contrário
     * @throws PDOException Se ocorrer um erro na inserção no banco de dados
     */
    private function saveCompany($data)
    {
        $sql = "INSERT INTO companies (cnpj, name, trade_name, address, city, state, zip_code, phone, email, website) 
                VALUES (:cnpj, :name, :trade_name, :address, :city, :state, :zip_code, :phone, :email, :website)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
