from flask import Flask, request, jsonify, send_from_directory, abort
from flask_cors import CORS
import os
import json
from werkzeug.utils import secure_filename

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
UPLOAD_DIR = os.path.join(ROOT, 'backend', 'uploads', 'pdfs')
DATA_FILE = os.path.join(ROOT, 'backend', 'data', 'resources.json')
ALLOWED_EXT = {'pdf'}

os.makedirs(UPLOAD_DIR, exist_ok=True)

app = Flask(__name__, static_folder=ROOT, static_url_path='')
CORS(app)

def load_resources():
    if not os.path.exists(DATA_FILE):
        return []
    try:
        with open(DATA_FILE, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception:
        return []

def save_resources(items):
    with open(DATA_FILE, 'w', encoding='utf-8') as f:
        json.dump(items, f, ensure_ascii=False, indent=2)

@app.route('/backend/api/resources.php', methods=['GET', 'POST', 'DELETE', 'OPTIONS'])
def resources_api():
    if request.method == 'OPTIONS':
        return ('', 200)

    resources = load_resources()

    if request.method == 'GET':
        published = [r for r in resources if r.get('status', 'draft') == 'published']
        return jsonify({'success': True, 'resources': published})

    if request.method == 'POST':
        title = request.form.get('title', '').strip()
        category = request.form.get('category', '').strip()
        description = request.form.get('description', '').strip()

        if not title or not category or not description:
            return jsonify({'success': False, 'message': 'Judul, kategori, dan deskripsi wajib diisi.'}), 400

        if 'pdf_file' not in request.files:
            return jsonify({'success': False, 'message': 'File PDF wajib diunggah.'}), 400

        file = request.files['pdf_file']
        filename = secure_filename(file.filename)
        ext = filename.rsplit('.', 1)[-1].lower() if '.' in filename else ''
        if ext not in ALLOWED_EXT:
            return jsonify({'success': False, 'message': 'File harus berformat PDF.'}), 400

        safe_name = f"{os.path.splitext(filename)[0]}-{int(__import__('time').time())}.pdf"
        target_path = os.path.join(UPLOAD_DIR, safe_name)
        try:
            file.save(target_path)
        except Exception as e:
            return jsonify({'success': False, 'message': 'Gagal menyimpan file PDF.'}), 500

        new_item = {
            'id': int(__import__('time').time()),
            'title': title,
            'category': category,
            'category_label': category.capitalize(),
            'description': description,
            'read_time': request.form.get('read_time') or '5 menit baca',
            'type': 'pdf',
            'status': 'published',
            'file_path': f'backend/uploads/pdfs/{safe_name}'
        }

        resources.append(new_item)
        save_resources(resources)
        return jsonify({'success': True, 'message': 'Buku PDF berhasil ditambahkan.', 'resource': new_item})

    if request.method == 'DELETE':
        try:
            data = request.get_json() or {}
            rid = int(data.get('id', 0))
        except Exception:
            return jsonify({'success': False, 'message': 'ID tidak valid.'}), 400

        updated = []
        for item in resources:
            if int(item.get('id', 0)) == rid:
                fp = item.get('file_path')
                if fp:
                    fp_full = os.path.join(ROOT, fp.replace('/', os.sep))
                    if os.path.exists(fp_full):
                        os.remove(fp_full)
                continue
            updated.append(item)
        save_resources(updated)
        return jsonify({'success': True, 'message': 'Buku berhasil dihapus.'})

    return jsonify({'success': False, 'message': 'Method tidak diizinkan.'}), 405

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8001, debug=True)
