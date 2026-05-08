import sys
import json
import re
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

def preprocess_text(text, stemmer, stopword_remover):
    """Lakukan preprocessing teks lengkap"""
    if not isinstance(text, str) or not text.strip():
        return ""
    
    # Case folding
    text = text.lower()
    
    # Filtering: hapus karakter khusus, angka, dll
    text = re.sub(r'[^a-zA-Z\s]', ' ', text)
    
    # Tokenization sederhana
    tokens = text.split()
    
    # Stopword removal
    filtered_text = stopword_remover.remove(' '.join(tokens))
    tokens = filtered_text.split()
    
    # Stemming
    stemmed_tokens = [stemmer.stem(word) for word in tokens]
    
    # Filter kata dengan panjang < 2 hanya di akhir (satu kali saja)
    final_tokens = [word for word in stemmed_tokens if len(word) >= 2]
    
    return ' '.join(final_tokens)

if __name__ == "__main__":
    try:
        # Inisialisasi Sastrawi
        factory = StemmerFactory()
        stemmer = factory.create_stemmer()
        
        stopword_factory = StopWordRemoverFactory()
        stopword_remover = stopword_factory.create_stop_word_remover()
        
        # Baca input dari Laravel
        input_data = json.loads(sys.argv[1])
        
        # Preprocessing judul dan ringkasan secara terpisah
        judul_preprocessed = preprocess_text(input_data['judul'], stemmer, stopword_remover)
        ringkasan_preprocessed = preprocess_text(input_data['ringkasan'], stemmer, stopword_remover)
        
        # Gabungkan hasil preprocessing judul dan ringkasan
        combined_preprocessing = []
        
        if judul_preprocessed.strip():
            combined_preprocessing.append(judul_preprocessed)
        
        if ringkasan_preprocessed.strip():
            combined_preprocessing.append(ringkasan_preprocessed)
        
        # Gabungkan dengan spasi sebagai pemisah
        final_preprocessing = ' '.join(combined_preprocessing)
        
        # Output hasil gabungan sebagai string tunggal
        result = {
            'id': input_data['id'],
            'preprocessing': final_preprocessing
        }
        
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(1)