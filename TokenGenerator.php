<?php
/**
 * TokenGenerator - Generador de tokens seguros para CSRF y 2FA
 * 
 * Parte de la solución mejorada de seguridad para Quipux
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

class TokenGenerator {
    
    /**
     * Longitud del token en bytes
     */
    private const TOKEN_LENGTH = 32;
    
    /**
     * Tiempo de vida del token CSRF en segundos (1 hora)
     */
    private const CSRF_TOKEN_LIFETIME = 3600;
    
    /**
     * Tiempo de vida del token 2FA en segundos (10 minutos)
     */
    private const TWO_FA_TOKEN_LIFETIME = 600;
    
    /**
     * Generar un token CSRF seguro
     * 
     * @return string Token CSRF en formato hexadecimal
     */
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
    
    /**
     * Generar un token 2FA (código numérico de 6 dígitos)
     * 
     * @return string Código de 6 dígitos
     */
    public static function generate2FAToken() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generar un token de recuperación de contraseña
     * 
     * @return string Token seguro para recuperación
     */
    public static function generatePasswordResetToken() {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
    
    /**
     * Generar un token de sesión seguro
     * 
     * @return string Token de sesión criptográficamente seguro
     */
    public static function generateSessionToken() {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
    
    /**
     * Validar un token CSRF
     * 
     * @param string $token Token a validar
     * @param string $storedToken Token almacenado en sesión
     * @param int $tokenTime Timestamp de creación del token
     * @return bool True si el token es válido
     */
    public static function validateCSRFToken($token, $storedToken, $tokenTime) {
        // Validar que el token no haya expirado
        if ((time() - $tokenTime) > self::CSRF_TOKEN_LIFETIME) {
            return false;
        }
        
        // Usar comparación de tiempo constante para evitar timing attacks
        return hash_equals($token, $storedToken);
    }
    
    /**
     * Validar un token 2FA
     * 
     * @param string $token Token a validar
     * @param string $storedToken Token almacenado en sesión
     * @param int $tokenTime Timestamp de creación del token
     * @return bool True si el token es válido
     */
    public static function validate2FAToken($token, $storedToken, $tokenTime) {
        // Validar que el token no haya expirado
        if ((time() - $tokenTime) > self::TWO_FA_TOKEN_LIFETIME) {
            return false;
        }
        
        // Usar comparación de tiempo constante
        return hash_equals($token, $storedToken);
    }
    
    /**
     * Generar un token JWT simple para APIs
     * 
     * @param array $payload Datos a incluir en el token
     * @param string $secret Clave secreta para firmar
     * @param int $expiresIn Tiempo de expiración en segundos
     * @return string Token JWT
     */
    public static function generateJWT($payload, $secret, $expiresIn = 3600) {
        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        // Payload
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;
        
        // Codificar
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Firmar
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Validar un token JWT
     * 
     * @param string $token Token JWT a validar
     * @param string $secret Clave secreta
     * @return array|false Payload si es válido, false en caso contrario
     */
    public static function validateJWT($token, $secret) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verificar firma
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );
        $expectedSignature = self::base64UrlEncode($signature);
        
        if (!hash_equals($signatureEncoded, $expectedSignature)) {
            return false;
        }
        
        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false;
        }
        
        // Validar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Codificar en base64 URL-safe
     * 
     * @param string $data Datos a codificar
     * @return string Datos codificados
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificar desde base64 URL-safe
     * 
     * @param string $data Datos a decodificar
     * @return string Datos decodificados
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
    
}
?>
