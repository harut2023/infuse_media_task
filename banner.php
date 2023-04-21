<?php

define('HOST','localhost');
define('DB','gssamru_infuse');
define('USERNAME','gssamru_infuse');
define('PASSWORD','x{#IEXaLPUuo');
define('TABLE','logger');


/**
 * Class DBConnection
 * @author Harut
 */
interface DBConnection
{
}

/**
 * Class Settings
 * @author Harut
 */
final class ConnectionSettings
{
    private $host = "";
    private $dbName = "";
    private $username = "";
    private $password = "";

    /**
     * @param string $host
     * @param string $dbName
     * @param string $username
     * @param string $password
     */
    public function __construct(string $host, string $dbName, string $username, string $password)
    {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public static function getTable(): string
    {
        return TABLE;
    }

}

class Helper
{
    /**
     * @return string
     */
    public static function getPageUrl()
    {
        file_put_contents("log.txt", print_r($_SERVER, true));
        $url = '';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $url = "https://";
        } else {
            $url = "http://";
        }

        return $url.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    /**
     * @return int
     */
    public static function generateRandomNumber():int
    {
        return rand(0,256);
    }
}

/**
 * Class PDOConnection
 * @author Harut
 */
class PDOConnection implements DBConnection
{
    private $connection;
    private static $_instance;


    public function __clone(){}
    protected function __construct() { }

    /**
     * @return self
     */
    public static function getInstance(): DBConnection
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param ConnectionSettings $settings
     */
    public function connect(ConnectionSettings $settings): void
    {
        try {
            $this->connection = new PDO('mysql:host=' . $settings->getHost() . ';dbname=' . $settings->getDbName(),
                $settings->getUsername(), $settings->getPassword());
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function closeConnection()
    {
        $this->connection = null;
    }

}

interface QueryBuilder
{
    public function insertOrUpdate():bool;
}

/**
 * Class AbstractModel
 */
abstract class AbstractModel
{
    /**
     * @var DBConnection
     */
    protected $connection;

    /**
     * @param QueryBuilder $builder
     */
    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection->getConnection();
    }
}

class Logger extends AbstractModel implements QueryBuilder
{
    protected string $table = '';
    protected string $ipAddress;
    protected string $userAgent;
    protected string $pageUrl;
    protected int $viewsCount = 0;

    public function __construct(DBConnection $connection)
    {
        $this->setTable();
        $this->viewsCount += 1;
        parent::__construct($connection);
    }

    public function setTable()
    {
        $this->table = ConnectionSettings::getTable();
    }

    /**
     * @param $ipAddress
     * @param $userAgent
     * @param $pageUrl
     * @return void
     */
    public function setProperties($ipAddress, $userAgent, $pageUrl):void
    {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->pageUrl = $pageUrl;
    }

    /**
     * @return boolean
     */
    /**
     * @return bool
     */
    public function insertOrUpdate(): bool
    {

        try {
            $sql = "INSERT INTO $this->table (`ip_address`, `user_agent`, `page_url`,`view_date`, `views_count`) 
                VALUES (:ip, :userAgent, :pageUrl, NOW(), :viewsCount) ON DUPLICATE KEY UPDATE `views_count` =views_count+1";
            $prep = $this->connection->prepare($sql);
            $prep->execute([':ip'=>$this->ipAddress , ':userAgent' => $this->userAgent, ':pageUrl'=>$this->pageUrl, ':viewsCount'=>$this->viewsCount]);

        } catch (PDOException$e) {
            echo "Failed To Insert : " . $e->getMessage();
        }

        return true;
    }
}


class RGB
{
    private int $r;
    private int $g;
    private int $b;

    public function __construct(int $r, int $g, int $b)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    /**
     * @return int
     */
    public function getR(): int
    {
        return $this->r;
    }

    /**
     * @return int
     */
    public function getG(): int
    {
        return $this->g;
    }

    /**
     * @return int
     */
    public function getB(): int
    {
        return $this->b;
    }
}

$settings = new ConnectionSettings(HOST, DB, USERNAME, PASSWORD);
$connection = PDOConnection::getInstance();
$connection->connect($settings);
$logger = new Logger($connection);
$logger->setProperties($_SERVER['REMOTE_ADDR'] , $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_REFERER']);
$logger->insertOrUpdate();

class ImageSettings
{
    private float $width;
    private float $height;
    private RGB $rgb;

    /**
     * @param float $width
     * @param float $height
     * @param RGB $rgb
     */
    public function __construct(float $width, float $height, RGB $rgb)
    {
        $this->width = $width;
        $this->height = $height;
        $this->rgb = $rgb;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * @return RGB
     */
    public function getRgb(): RGB
    {
        return $this->rgb;
    }
}

class Image
{
    private static $_instance;
    private $image;

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(ImageSettings $settings)
    {
        $this->image = imagecreatetruecolor($settings->getWidth(), $settings->getHeight());
        $color = imagecolorallocate($this->image, $settings->getRgb()->getR(), $settings->getRgb()->getG(), $settings->getRgb()->getB());
        imagefill($this->image, 0, 0, $color);
        header('Content-Type: image/jpeg');
        imagejpeg($this->image);
        imagedestroy($this->image);
    }

}
$rgb = new RGB(Helper::generateRandomNumber(),Helper::generateRandomNumber(),Helper::generateRandomNumber());
$setting = new ImageSettings(150,250, $rgb);
$image = new Image($setting);

