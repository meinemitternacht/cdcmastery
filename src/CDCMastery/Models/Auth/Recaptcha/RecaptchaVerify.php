<?php
declare(strict_types=1);


namespace CDCMastery\Models\Auth\Recaptcha;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Log\LoggerInterface;

class RecaptchaVerify
{
    private LoggerInterface $log;
    private string $secret;
    private string $token;
    private string $client_ip;

    /**
     * RecaptchaVerify constructor.
     * @param LoggerInterface $log
     * @param string $secret
     * @param string $token
     * @param string $client_ip
     */
    public function __construct(LoggerInterface $log, string $secret, string $token, string $client_ip)
    {
        $this->log = $log;
        $this->secret = $secret;
        $this->token = $token;
        $this->client_ip = $client_ip;
    }

    public function verify(): bool
    {
        $params = [
            'form_params' => [
                'secret' => $this->secret,
                'response' => $this->token,
                'remoteip' => $this->client_ip,
            ],
        ];

        try {
            $response = (new Client(['base_uri' => 'https://www.google.com']))->post('/recaptcha/api/siteverify',
                                                                                     $params);
            $body = $response->getBody();
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($json)) {
                $this->log->debug('reCAPTCHA verification response json_decode() failed');
                goto out_error;
            }

            $success = (bool)($json[ 'success' ] ?? false);
            $errors = $json[ 'error-codes' ] ?? [];

            if (is_array($errors) && $errors) {
                foreach ($errors as $error) {
                    $this->log->debug("reCAPTCHA error :: {$error}");
                }

                goto out_error;
            }

            return $success;
        } catch (GuzzleException $e) {
            $this->log->debug($e);
            $this->log->debug('reCAPTCHA verification response Guzzle request failed');
            goto out_error;
        } catch (JsonException $e) {
            $this->log->debug($e);
            $this->log->debug('reCAPTCHA verification response json_decode() failed');
            goto out_error;
        }

        out_error:
        $this->log->debug("reCAPTCHA token :: {$this->token}");
        $this->log->debug("reCAPTCHA client IP :: {$this->client_ip}");
        return false;
    }
}