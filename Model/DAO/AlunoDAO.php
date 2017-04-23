<?php
/**
 * Created by PhpStorm.
 * User: Alberto
 * Date: 21/04/2017
 * Time: 23:20
 */
require_once ( "Controller/includes/Conexao.php");
require_once ("Controller/includes/GeraLog.php");
require_once ("DaoCrud.php");
require_once  ("Model/Bean/Aluno.php");
class AlunoDAO extends DaoCrud
{

    public function create($aluno)
    {
        try {
            if(count($aluno->getAlunoFazCurso())<=0)
                return false;//Erro Falta de Matricula
            Conexao::getInstance()->beginTransaction();
            $sql = "INSERT INTO aluno (
                matricula,
                nome,
                ano_Periodo,
                email,
                nascimento) 
                VALUES (
                :matricula,
                :nome,
                :ano_Periodo,
                :email,
                :nascimento)";
            $stmt = Conexao::getInstance()->prepare($sql);
            $stmt->bindValue(":matricula", $aluno->getMatricula());
            $stmt->bindValue(":nome", $aluno->getNome());
            $stmt->bindValue(":ano_Periodo", $aluno->getAnoPeriodo());
            $stmt->bindValue(":email", $aluno->getEmail());
            $stmt->bindValue(":nascimento", $aluno->getNascimento());
            $stmt->execute();
            foreach ($aluno->getAlunoFazCurso() as $Curso_idCurso) {
                /*$sql = "SELECT COUNT(*) FROM curso WHERE idCurso = :idCurso";
                $stmt = Conexao::getInstance()->prepare($sql);
                $stmt->bindValue(":idCurso", $Curso_idCurso, PDO::PARAM_INT);
                $stmt->execute();
                if($stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"] == 0) {
                    Conexao::getInstance()->rollback();
                    return Array($Curso_idCurso, 0); // Curso não identificado
                }*/
                $sql = "INSERT INTO aluno_faz_curso(
                    Curso_idCurso,
                    Aluno_matricula) 
                    VALUES (
                    :Curso_idCurso,
                    :Aluno_matricula)";
                $stmt = Conexao::getInstance()->prepare($sql);
                $stmt->bindValue(":Curso_idCurso", $Curso_idCurso);
                $stmt->bindValue(":Aluno_matricula", $aluno->getMatricula());
                $stmt->execute();
            }
            Conexao::getInstance()->commit();
            return $aluno;
        }catch (Exception $e){
            Conexao::getInstance()->rollBack();
            GeraLog::getInstance()->inserirLog("Erro: Código: " . $e->getCode() . " Mensagem: " . $e->getMessage());
            die("Ocorreu um erro ao tentar executar esta ação, foi gerado um LOG do mesmo, tente novamente mais tarde.");
            //return NULL;
        }
    }

    public function read($matricula) : Aluno
    {
        try {
            Conexao::getInstance()->beginTransaction();
            $sql = "SELECT * FROM aluno WHERE matricula = :matricula";
            $stmt = Conexao::getInstance()->prepare($sql);
            $stmt->bindValue(":matricula", $matricula, PDO::PARAM_STR);
            $stmt->execute();
            $obj = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = Conexao::getInstance()->prepare("SELECT * FROM aluno_faz_curso WHERE Aluno_matricula = :Aluno_matricula ");
            $stmt->bindValue(":Aluno_matricula", $matricula, PDO::PARAM_STR);
            $stmt->execute();
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $aux = Array();
            foreach ($cursos as $curso)
                array_push($aux, $curso["Curso_idCurso"]);
            Conexao::getInstance()->commit();
            return new Aluno($obj["matricula"], intval($obj["ano_Periodo"]), $obj["nome"], $obj["email"], $obj["nascimento"], $aux);
        } catch (Exception $e) {
            Conexao::getInstance()->rollBack();
            GeraLog::getInstance()->inserirLog("Erro: Código: " . $e->getCode() . " Mensagem: " . $e->getMessage());
            die("Ocorreu um erro ao tentar executar esta ação, foi gerado um LOG do mesmo, tente novamente mais tarde.");
        }
    }

    public function update($aluno)
    {
        try {
            $sql = "UPDATE aluno set
                nome = :nome,
                ano_Periodo = :ano_Periodo,
                email = :email,
                nascimento = :nascimento
                WHERE matricula = :matricula";
            $stmt = Conexao::getInstance()->prepare($sql);
            $stmt->bindValue(":nome", $aluno->getNome());
            $stmt->bindValue(":ano_Periodo", $aluno->getAnoPeriodo());
            $stmt->bindValue(":email", $aluno->getEmail());
            $stmt->bindValue(":nascimento", $aluno->getNascimento());
            $stmt->bindValue(":matricula", $aluno->getMatricula());
            return $stmt->execute();
        } catch (Exception $e) {
            GeraLog::getInstance()->inserirLog("Erro: Código: " . $e->getCode() . " Mensagem: " . $e->getMessage());
            die("Ocorreu um erro ao tentar executar esta ação, foi gerado um LOG do mesmo, tente novamente mais tarde.");
        }
    }

    public function delete($matricula)
    {
        try {
            Conexao::getInstance()->beginTransaction();
            $sql = "DELETE FROM aluno_faz_curso WHERE Aluno_matricula = :Aluno_matricula";
            $stmt = Conexao::getInstance()->prepare($sql);
            $stmt->bindValue(":Aluno_matricula", $matricula);
            $stmt->execute();
            $sql = "DELETE FROM aluno WHERE matricula = :matricula";
            $stmt = Conexao::getInstance()->prepare($sql);
            $stmt->bindValue(":matricula", $matricula);
            $stmt->execute();
            Conexao::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            Conexao::getInstance()->rollBack();
            GeraLog::getInstance()->inserirLog("Erro: Código: " . $e->getCode() . " Mensagem: " . $e->getMessage());
            die("Ocorreu um erro ao tentar executar esta ação, foi gerado um LOG do mesmo, tente novamente mais tarde.");
            //return false;
        }
    }
}