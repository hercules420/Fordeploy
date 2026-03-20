class ConsumerSession {
  const ConsumerSession({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.token,
    this.phone,
    this.location,
  });

  final int id;
  final String name;
  final String email;
  final String role;
  final String token;
  final String? phone;
  final String? location;

  ConsumerSession copyWith({
    String? name,
    String? phone,
    String? location,
  }) {
    return ConsumerSession(
      id: id,
      name: name ?? this.name,
      email: email,
      role: role,
      token: token,
      phone: phone ?? this.phone,
      location: location ?? this.location,
    );
  }

  factory ConsumerSession.fromJson(Map<String, dynamic> json) {
    int parseInt(dynamic value) => int.tryParse(value.toString()) ?? 0;

    return ConsumerSession(
      id: parseInt(json['id']),
      name: (json['name'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      role: (json['role'] ?? 'consumer') as String,
      token: (json['token'] ?? '') as String,
      phone: json['phone'] as String?,
      location: json['location'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'role': role,
      'token': token,
      'phone': phone,
      'location': location,
    };
  }
}
