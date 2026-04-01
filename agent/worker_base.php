<?php
/**
 * Worker Base Class
 * Provides logging and DB connectivity for specialized worker agents.
 */
class AgentWorker {
    protected $pdo;
    protected $taskId;
    protected $agentName;

    public function __construct($pdo, $taskId, $agentName) {
        $this->pdo = $pdo;
        $this->taskId = $taskId;
        $this->agentName = $agentName;
    }

    public function log($message, $isError = false) {
        $stmt = $this->pdo->prepare("INSERT INTO agent_logs (task_id, agent_name, action, detail) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->taskId, $this->agentName, $isError ? 'error' : 'log', $message]);
        echo "[$this->agentName] $message\n";
    }

    public function setStatus($status, $result = null) {
        $stmt = $this->pdo->prepare("UPDATE agent_tasks SET status = ?, result = ? WHERE id = ?");
        $stmt->execute([$status, $result, $this->taskId]);
    }
}
